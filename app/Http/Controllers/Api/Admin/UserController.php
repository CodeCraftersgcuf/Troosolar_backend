<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\ActivityHelper;
use App\Http\Requests\ForgetPasswordRequest;
use Exception;
use App\Models\User;
use App\Models\Wallet;
use App\Mail\SendOtpMail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Carbon;
use App\Helpers\ResponseHelper;
use App\Http\Requests\UserRequest;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResetPassword;
use App\Http\Requests\UpdateRequest;
use App\Http\Requests\VerifyForgetOtpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    /**
     * Register a new user.
     * @param UserRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
public function index()
{
    return $this->allUsers();
}
    // User register
   public function register(UserRequest $request)
{
    try {
        $data = $request->validated();

        // Validate required fields
        if (empty($data['email'])) {
            return ResponseHelper::error('Email is required', 422);
        }

        if (empty($data['password'])) {
            return ResponseHelper::error('Password is required', 422);
        }

        if (empty($data['first_name'])) {
            return ResponseHelper::error('First name is required', 422);
        }

        // Check if email already exists
         if (User::where('email', $data['email'])->exists()) {
            return ResponseHelper::error('Email is already registered', 409);
        }

        // Handle profile picture upload
        if (isset($data['profile_picture']) && $data['profile_picture']->isValid()) {
            try {
                $uploadPath = public_path('/users');
                // Create directory if it doesn't exist
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

            $img = $data['profile_picture'];
            $ext = $img->getClientOriginalExtension();
                $imageName = time() . '_' . Str::random(10) . '.' . $ext;
                $img->move($uploadPath, $imageName);
            $data['profile_picture'] = 'users/' . $imageName;
            } catch (\Exception $e) {
                Log::warning('Profile picture upload failed: ' . $e->getMessage());
                // Continue without profile picture
                unset($data['profile_picture']);
            }
        }

        // Note: Password is auto-hashed by User model's 'hashed' cast
        // So we don't need to manually hash it here

        // Generate user code and OTP
        $data['user_code'] = Str::lower($data['first_name']) . rand(100, 999);
        $data['otp'] = rand(10000, 99999);
        
        // Set default values
        $data['role'] = $data['role'] ?? 'user'; // Default to 'user' if role not provided
        $data['is_active'] = $data['is_active'] ?? true;
        $data['is_verified'] = $data['is_verified'] ?? false;

        // Create user
        $user = User::create($data);

        if (!$user) {
            Log::error('User creation failed', ['data' => $data]);
            return ResponseHelper::error('Failed to create user account', 500);
        }

        // Create wallet for user
        try {
        $this->createWallet($user);
        } catch (\Exception $e) {
            Log::error('Wallet creation failed for user: ' . $user->id, ['error' => $e->getMessage()]);
            // Continue even if wallet creation fails - user is already created
        }

        // Send OTP email
        try {
        Mail::to($user->email)->send(new SendOtpMail($user->otp, $user));
        } catch (\Exception $e) {
            Log::warning('OTP email sending failed for user: ' . $user->id, ['error' => $e->getMessage()]);
            // Continue even if email fails - user is registered
        }

        // Hide sensitive data from response
        $user->makeHidden(['password', 'otp']);

        return ResponseHelper::success($user, 'User registered successfully', 201);
    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error('Registration validation error', ['errors' => $e->errors()]);
        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $ex) {
        Log::error('Registration error: ' . $ex->getMessage(), [
            'trace' => $ex->getTraceAsString(),
            'file' => $ex->getFile(),
            'line' => $ex->getLine()
        ]);
        return ResponseHelper::error('Registration failed: ' . $ex->getMessage(), 500);
    }
}

public function addUser(UserRequest $request){
    try{
        $data=$request->validated();

        if (User::where('email', $data['email'])->exists()) {
            return ResponseHelper::error('Email is already registered', 409);
        }
            if (isset($data['profile_picture']) && $data['profile_picture']->isValid()) {
            $img = $data['profile_picture'];
            $ext = $img->getClientOriginalExtension();
            $imageName = time() . '.' . $ext;
            $img->move(public_path('/users'), $imageName);
            $data['profile_picture'] = 'users/' . $imageName;

        }

        $data['user_code'] = Str::lower($data['first_name']) . rand(100, 999);
        //no need of otp
        $user = User::create($data);
        $this->createWallet($user);
        return ResponseHelper::success($user, 'User registered successfully', 201);

    }catch(Exception $ex){
        return ResponseHelper::error('User is not registered', 500);
    }
}
// verify OTP
public function verifyOtp(Request $request, $user_id)
{
    try {
        $data = $request->validate(['verify_otp' => 'required']);
        $user = User::findOrFail($user_id);

        if ($data['verify_otp'] != $user->otp) {
            Log::warning('Invalid OTP', ['provided' => $data['verify_otp'], 'expected' => $user->otp]);
            return ResponseHelper::error('Invalid OTP', 400);
        }

        return ResponseHelper::success(null, 'OTP verified successfully');
    } catch (Exception $e) {
        return ResponseHelper::error('OTP verification failed', 500);
    }
}

// login user
public function login(LoginRequest $request)
{
    try {
        $user = $request->validated();
        Log::info('Login attempt', ['email' => $user['email']]);

        if (Auth::attempt($user)) {
            $authUser = Auth::user();
            $token = $authUser->createToken("API Token")->plainTextToken;
            
            // Save activity, but don't fail login if activity saving fails
            try {
                ActivityHelper::saveActivity($authUser->id,'User Logged In');
            } catch (Exception $activityException) {
                Log::warning('Failed to save login activity', [
                    'user_id' => $authUser->id,
                    'error' => $activityException->getMessage()
                ]);
            }
            
            return response()->json([
                'status' => true,
                'message' => 'Login Successfully',
                'token_type' => 'bearer',
                'token' => $token,
                'user' => $authUser,
            ], 200);
        } else {
            return ResponseHelper::error('Invalid credentials', 401);
        }
    } catch (Exception $e) {
        Log::error('Login failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        return ResponseHelper::error('Login failed: ' . $e->getMessage(), 500);
    }
}
public function adminLogin(LoginRequest $request)
{
    try {
        $user = $request->validated();
        Log::info('Login attempt', ['email' => $user['email']]);

        if (Auth::attempt($user)) {
            $authUser = Auth::user();
            if($authUser->role != 'admin' && $authUser->role != 'super_admin'){
                return ResponseHelper::error('Unauthorized access', 403);
            }
            $token = $authUser->createToken("API Token")->plainTextToken;
            
            // Save activity, but don't fail login if activity saving fails
            try {
                ActivityHelper::saveActivity($authUser->id,'User Logged In');
            } catch (Exception $activityException) {
                Log::warning('Failed to save login activity', [
                    'user_id' => $authUser->id,
                    'error' => $activityException->getMessage()
                ]);
            }
            
            return response()->json([
                'status' => true,
                'message' => 'Login Successfully',
                'token_type' => 'bearer',
                'token' => $token,
                'user' => $authUser,
            ], 200);
        } else {
            return ResponseHelper::error('Invalid credentials', 401);
        }
    } catch (Exception $e) {
        Log::error('Login failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        return ResponseHelper::error('Login failed: ' . $e->getMessage(), 500);
    }
}

// logout user
public function logout(Request $request)
{
    try {
        $user = $request->user();
        if ($user) {
            $user->tokens()->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Logged out successfully.'
            ]);
        }
        return ResponseHelper::success($user, 'User logged out successfully');
    } catch (Exception $e) {
        return ResponseHelper::error($e->getMessage());
    }
}

//password reset routes
public function forgetPassword(ForgetPasswordRequest $request){
    $data=$request->validated();
    $email=$data['email'];
    $user=User::where('email',$email)->first();
    if(!$user){
        return ResponseHelper::error('User not found',404);
    }
    $user->otp=rand(10000,99999);
    $user->save();
    Mail::to($user->email)->send(new SendOtpMail($user->otp,$user));
    return ResponseHelper::success(null,'OTP sent successfully');

}
public function verifyResetPasswordOtp(VerifyForgetOtpRequest $request){
    $data=$request->validated();
    $user=User::where('email',$data['email'])->first();
    if(!$user){
        return ResponseHelper::error('User not found',404);
    }
    if($user->otp!=$data['otp']){
        return ResponseHelper::error('Invalid OTP',400);
    }
    $user->otp=null;
    $user->save();
    return ResponseHelper::success(null,'OTP verified successfully');
}

public function resetPassword(ResetPassword $request){
    $data=$request->validated();
    $user=User::where('email',$data['email'])->first();
    if(!$user){
        return ResponseHelper::error('User not found',404);
    }
    $user->password=Hash::make($data['password']);
    $user->save();
    return ResponseHelper::success(null,'Password reset successfully');

   

}
// get all users
public function allUsers()
{
    try {
        $users = User::all();
         $total = User::count();
        $nweUser = User::whereMonth('created_at', Carbon::now()->month)->count();
        $usersWithLoanBalance = User::whereHas('wallet', function ($query) {
    $query->where('loan_balance', '>', 0);
})->count();
    $data = [
        'total_users' => $total,
        'new user' => $nweUser,
        'user with loan balance' => $usersWithLoanBalance,
        'all users data' => $users
    ];
        return ResponseHelper::success($data, 'Users retrieved successfully');
    } catch (Exception $ex) {
        return ResponseHelper::error('Users not found', 404);
    }
}

// update user profile
public function updateUser(UpdateRequest $request)
{
    try {
        $data = $request->validated();
        // dd($data);
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception("User not found");
        }

        if (isset($data['profile_picture']) && is_object($data['profile_picture']) && is_file($data['profile_picture']->getPathname())) {
            $img = $data['profile_picture'];
            $oldImagePath = public_path('users/' . $user->profile_picture);

            if (file_exists($oldImagePath)) {
                @unlink($oldImagePath);
            }

            $ext = $img->getClientOriginalExtension();
            $imageName = time() . '.' . $ext;
            $img->move(public_path('users'), $imageName);
            $data['profile_picture'] = $imageName;
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        User::where('id', Auth::id())->update($data);
        return ResponseHelper::success($data, 'User profile updated successfully');
    } catch (Exception $e) {
        throw new Exception('User profile update failed: ' . $e->getMessage());
    }
}
public function updateUserByAdmin(UpdateRequest $request,$userId)
{
    try {
        $data = $request->validated();
        // dd($data);
        $user = User::find($userId);
        if (!$user) {
            throw new Exception("User not found");
        }

        if (isset($data['profile_picture']) && is_object($data['profile_picture']) && is_file($data['profile_picture']->getPathname())) {
            $img = $data['profile_picture'];
            $oldImagePath = public_path('users/' . $user->profile_picture);

            if (file_exists($oldImagePath)) {
                @unlink($oldImagePath);
            }

            $ext = $img->getClientOriginalExtension();
            $imageName = time() . '.' . $ext;
            $img->move(public_path('users'), $imageName);
            $data['profile_picture'] = $imageName;
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        User::where('id', $userId)->update($data);
        return ResponseHelper::success($data, 'User profile updated successfully');
    } catch (Exception $e) {
        throw new Exception('User profile update failed: ' . $e->getMessage());
    }
}

// send otp
public function sendotp()
{
    try
    {
        $user = Auth::user();
        if (!$user) {
            return ResponseHelper::error('User not authenticated', 401);
        }

        $user->otp = rand(10000, 99999);
        $user->save();

        Mail::to($user->email)->send(new SendOtpMail($user->otp, $user));
        Log::info('OTP sent to user', ['user_id' => $user->id, 'email' => $user->email]);

        return ResponseHelper::success(null, 'OTP sent successfully');
    } catch (Exception $e) {
        Log::error('Failed to send OTP', ['message' => $e->getMessage()]);
        return ResponseHelper::error('Failed to send OTP', 500);
    }
}
// delete user
public function deleteUser($userId)
{
    try {
        $user = User::find($userId);
        if (!$user) {
            Log::warning('User not found for deletion', ['user_id' => $userId]);
            return ResponseHelper::error('User not found', 404);
        }

        User::where('id', $userId)->delete();
        Log::info('User deleted', ['user_id' => $userId]);
        return ResponseHelper::success(null, 'User deleted successfully');
    } catch (Exception $e) {
        Log::error('User deletion failed', ['message' => $e->getMessage()]);
        return ResponseHelper::error('User deletion failed', 500);
    }
}

// create wallet for user
public function createWallet($user)
{
    $wallet = Wallet::create([
        'user_id' => $user->id,
        'status' => 'active'
    ]);
    Log::info('Wallet created for user', ['user_id' => $user->id, 'wallet_id' => $wallet->id]);
    return $wallet;
}

// get single user
public function singleUser($userId)
{
    try {
        $user = User::find($userId);
        if (!$user) {
            Log::warning('User not found', ['user_id' => $userId]);
            return ResponseHelper::error('User not found', 404);
        }
        $user->load('wallet','activitys');
        return ResponseHelper::success($user, 'User retrieved successfully');
    } catch (Exception $e) {
        Log::error('Error retrieving user', ['user_id' => $userId, 'message' => $e->getMessage()]);
        return ResponseHelper::error('Error retrieving user', 500);
    }
}

// Show users who applied for loan

    public function totalUser()

    {
        try
        {
            $total = User::count();
        $nweUser = User::whereMonth('created_at', Carbon::now()->month)->count();
        $usersWithLoanBalance = User::whereHas('wallet', function ($query) {
    $query->where('loan_balance', '>', 0);
})->count();
    $data = [
        'total_users' => $total,
        'new user' => $nweUser,
        'user with loan balance' => $usersWithLoanBalance
    ];
    return ResponseHelper::success($data, 'All users dataretrieved successfully');
    }
    catch (Exception $e)
    {
        return ResponseHelper::error('Error retrieving user', 500);
    }

}
}
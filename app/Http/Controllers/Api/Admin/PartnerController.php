<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\PartnerRequest;
use App\Jobs\SendUserLoanInfoToPartnerJob;
use App\Mail\SendUserLoanInfoToPartner;
use App\Models\LinkAccount;
use App\Models\LoanApplication;
use App\Models\LoanStatus;
use App\Models\Partner;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PartnerController extends Controller
{
    // Add partner
    public function add_partner(PartnerRequest $request)
    {
        try
        {
            $data = $request->validated();
            // Check for duplicate email
            if (!empty($data['email']) && Partner::where('email', $data['email'])->exists()) {
                return ResponseHelper::error('Email is already registered for a partner', 409);
            }
            $partner = Partner::create($data);
            return ResponseHelper::success($data, 'Partner is added succesfully');
        }
        catch(Exception $ex)
        {
            return ResponseHelper::error('Partner is not added: ' . $ex->getMessage());
        }
    }

    // All partner
    public function all_partners()
    {
        try
        {
            $all_partners = Partner::get();
            // dd($all_partners);
           $data = $all_partners->map(function ($partner) {
            return [
                'id'=> $partner->id,
                'Partner name' => $partner->name,
                'No of Loans' => $partner->no_of_loans,
                'Amount' => $partner->amount,
                'Date Created' => $partner->created_at,
                'Status' => $partner->status,
            ];
        });
            return ResponseHelper::success($data, 'Partner is fetch succesfully');
        }
        catch(Exception $ex)
        {
            return ResponseHelper::error('Not fetch the partners');
        }
    }

    // update partner
    
     public function update_partner( PartnerRequest $request, $partner_id)
    {
        try
        {
            $partner = Partner::findOrFail($partner_id);
            $data = $request->validated();

            // Check for duplicate email (exclude current partner)
            if (!empty($data['email']) && Partner::where('email', $data['email'])->where('id', '!=', $partner_id)->exists()) {
                return ResponseHelper::error('Email is already registered for another partner', 409);
            }

            $partner->update($data);
            return ResponseHelper::success($partner, 'Partner is updated successfully');
        }
        catch (ModelNotFoundException $e) {
            return ResponseHelper::error('Partner not found', 404);
        }
        catch(Exception $ex)
        {
            return ResponseHelper::error('Failed to update partner: ' . $ex->getMessage());
        }
    } 

    // delete partner
    public function delete_partner($partner_id)
    {
        try
        {
            $delete_partner = Partner::findOrFail($partner_id);
            $delete_partner->delete();
            return ResponseHelper::success('Partner is deleted successfully');
        }
        catch (ModelNotFoundException $e) {
            return ResponseHelper::error('Partner not found', 404);
        }
        catch(Exception $ex)
        {
            return ResponseHelper::error('Failed to delete partner: ' . $ex->getMessage());
        }
    }

    // send to partner user details (works for both loan and BNPL applications)
  public function sendToPartner(Request $request, string $userId)
{
    try {
        $request->validate(['partner_id' => 'required|exists:partners,id']);
        $user            = User::findOrFail($userId);
        $loanApplication = LoanApplication::where('user_id', $userId)->latest()->first();
        if (!$loanApplication) {
            return ResponseHelper::error('No loan application found for this user.', 404);
        }
        $linkAccount     = LinkAccount::where('user_id', $userId)->latest()->first();
        $partner         = Partner::findOrFail($request->partner_id);

        // Send mail instantly
        Mail::to($partner->email)->send(
            new SendUserLoanInfoToPartner($user, $loanApplication, $partner, $linkAccount)
        );

        // Only update LoanStatus if it exists (BNPL may not have a LoanStatus record)
        $loanStatus = LoanStatus::where('loan_application_id', $loanApplication->id)->first();
        if ($loanStatus) {
            $loanStatus->update([
                'send_status' => 'active',
                'send_date'   => now(),
                'partner_id'  => $partner->id
            ]);
        }

        return ResponseHelper::success('The email has been sent to the partner.');
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (Exception $ex) {
        Log::error('Error sending email to partner: ' . $ex->getMessage());
        return ResponseHelper::error('The email could not be sent to the partner.'.$ex->getMessage());
    }
}

}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'sur_name',
        'email',
        'password',
        'phone',
        'profile_picture',
        'refferal_code',
        'user_code',
        'role',
        'is_verified',
        'is_active',
        'otp',
        'bvn',
        'cart_access_token'
    ];

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    // loan history
    public function loanHistories()
    {
        return $this->hasMany(LoanHistory::class);
    }

    // loan installment
  public function loanInstallments()
{
    return $this->hasMany(LoanInstallment::class, 'user_id');
}

    // loan repayment
    public function loanRepayments()
{
    return $this->hasMany(LoanRepayment::class);
}
public function loans()
{
    return $this->hasMany(LoanHistory::class); // ✅ correct
}
public function wallets()
{
return $this->hasOne(Wallet::class);
}
public function activitys()
{
    return $this->hasMany(UserActivity::class);
}

// loan applications
public function loanApplications()
{
    return $this->hasMany(LoanApplication::class);
}

// orders
public function orders()
{
    return $this->hasMany(Order::class);
}

// referral relationships
public function referredUsers()
{
    return $this->hasMany(User::class, 'refferal_code', 'user_code');
}

public function referrer()
{
    return $this->belongsTo(User::class, 'refferal_code', 'user_code');
}

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

}
<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReferralController extends Controller
{
    public function getBalance(){
        $user=Auth::user();
        $wallet=Wallet::where('user_id', $user->id)->first();
        if(!$wallet){
            return ResponseHelper::error('Wallet not found');
        }
        $data=[
            'referral_code'=>$user->refferal_code,
            'referral_balance'=>$wallet->referral_balance
        ];
        return ResponseHelper::success($data,"Your Referral Balance");
    }
}

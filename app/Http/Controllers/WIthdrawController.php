<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\WithdrawRequestForm;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\WithdrawRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WIthdrawController extends Controller
{
    public function store(WithdrawRequestForm $request){
        try{
            $data=$request->validated();
            $amount=$data['amount'];
            $wallet=Wallet::where('user_id', Auth::id())->first();
            if($amount> $wallet->referral_balance){
                return ResponseHelper::error('Insufficient balance');
            }
            $wallet->referral_balance-=$amount;
            $wallet->save();
            $withdrawRequest=WithdrawRequest::create([
                'user_id'=>Auth::id(),
                'amount'=>$amount,
                'status'=>'pending',
                'bank_name'=>$data['bank_name'],
                'account_name'=>$data['account_name'],
                'account_number'=>$data['account_number']
            ]);

            return ResponseHelper::success($withdrawRequest,'Withdrawal successful');
        }catch(\Exception $e){
           return ResponseHelper::error($e->getMessage());
        }
    }
    public function getWithdrawRequest(){
        try{
            $withdrawRequest=WithdrawRequest::where('user_id',Auth::id())->latest()->get();
            return ResponseHelper::success($withdrawRequest,'Withdrawal successful');
        }catch(\Exception $e){
            return ResponseHelper::error($e->getMessage());
        }
    }
    public function approveRequest($id){
        try{
            $withdraw=WithdrawRequest::findOrFail($id);
            $withdraw->status='approved';
            // $withdraw->save();
            //create transaction for this 
            $transaction=Transaction::create([
                'user_id'=>$withdraw->user_id,
                'amount'=>$withdraw->amount,
                'status'=>$withdraw->status,
                'tx_id'=>$withdraw->id,
                'type'=>'withdrawal',
                'method'=>'Direct',
                'transacted_at'=>now(),
                'title'=>'Withdrawal'
            ]);
            $withdraw->transaction_id=$transaction->id;
            $withdraw->save();

            return ResponseHelper::success($withdraw,'Withdrawal successful');
        }catch(\Exception $e){
            return ResponseHelper::error($e->getMessage());
        }
    }
}

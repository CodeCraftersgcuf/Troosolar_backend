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
            $partner = Partner::create($data);
            return ResponseHelper::success($data, 'Partner is added succesfully');
        }
        catch(Exception $ex)
        {
            return ResponseHelper::error('Partner is not added ');
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
            $partner = Partner::findorfail( $partner_id);
            $data = $request->validated();
            $update_partner = Partner::where('id', $partner_id)->update($data);
            return ResponseHelper::success( $update_partner, 'Partner is updated succesfully');
        }
        catch(Exception $ex)
        {
            return ResponseHelper::error('the partner is update deleted');
        }
    } 

    // delete partner
    public function delete_partner($partner_id)
    {
        try
        {
            $delete_partner = Partner::findorfail( $partner_id);
            $delete_partner->delete();
            return ResponseHelper::success( 'Partner is deleted succesfully');
        }
        catch(Exception $ex)
        {
            return ResponseHelper::error('the partner is not deleted');
        }
    }

    // send to partner user details
    public function sendToPartner(Request $request, string $userId)
    {
        try
        {
            $user = User::where('id', $userId)->first();
            $loanApplication = LoanApplication::where('user_id', $userId)->latest()->first();
            $linkAccount = LinkAccount::where('user_id', $userId)->latest()->first();
            $partner_id = $request->partner_id;
            $partner = Partner::where('id', $partner_id)->first();
            // dd($linkAccount);
                // Mail::to($partner->email)->send(new SendUserLoanInfoToPartner($user, $loanApplication, $linkAccount, $partner));
                 dispatch(new SendUserLoanInfoToPartnerJob($user, $loanApplication, $partner, $linkAccount));
                 $loanSatatus = LoanStatus::where('loan_application_id', $loanApplication->id)->update([
                    'send_status' => 'active',
                    'send_date' => now(),
                 ]);
            return ResponseHelper::success('the email is send to partner');
        }
         catch(Exception $ex)
        {
            Log::error('Error sending email to partner: ' . $ex->getMessage());
            return ResponseHelper::error('the email is not send to partner');
        }

    }

}

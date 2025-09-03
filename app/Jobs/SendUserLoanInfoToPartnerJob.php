<?php

namespace App\Jobs;

use App\Mail\SendUserLoanInfoToPartner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendUserLoanInfoToPartnerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

     protected $user;
    protected $loanApplications;
    protected $partner;
    protected $linkAccount;

    public function __construct($user, $loanApplications, $partner, $linkAccount)
    {
        $this->user = $user;
        $this->loanApplications = $loanApplications;
        $this->partner = $partner;
        $this->linkAccount = $linkAccount;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
                Mail::to($this->partner->email)->send(
            new SendUserLoanInfoToPartner(
                $this->user,
                $this->loanApplications,
                $this->partner,
                $this->linkAccount
            )
        );

    }
}

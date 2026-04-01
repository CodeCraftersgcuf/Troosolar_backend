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

    protected $loanApplication;

    protected $partner;

    protected $linkAccount;

    public function __construct($user, $loanApplication, $partner, $linkAccount)
    {
        $this->user = $user;
        $this->loanApplication = $loanApplication;
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
                $this->loanApplication,
                $this->partner,
                $this->linkAccount
            )
        );

    }
}

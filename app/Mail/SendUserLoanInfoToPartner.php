<?php

namespace App\Mail;

use App\Models\LoanApplication;
use App\Services\PartnerLoanApplicationEmailPresenter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendUserLoanInfoToPartner extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    /** @var LoanApplication */
    public $loanApplication;

    public $partner;

    public $linkAccount;

    public string $emailSubject;

    /** @var array<string, mixed> */
    public array $viewData = [];

    /**
     * @param  \App\Models\User  $user
     * @param  LoanApplication  $loanApplication
     */
    public function __construct($user, $loanApplication, $partner, $linkAccount)
    {
        $this->user = $user;
        $this->loanApplication = $loanApplication;
        $this->partner = $partner;
        $this->linkAccount = $linkAccount;
        $this->emailSubject = $this->resolveSubject();
        $this->viewData = (new PartnerLoanApplicationEmailPresenter($user, $loanApplication))->build();
    }

    protected function resolveSubject(): string
    {
        $snap = $this->loanApplication->loan_plan_snapshot ?? null;
        if (is_array($snap) && count($snap) > 0) {
            return 'BNPL loan application – customer info for credit evaluation – Troosolar';
        }

        return 'Loan application – customer info for credit evaluation – Troosolar';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
            replyTo: [config('mail.from.address')],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.user_loan_info',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $out = [];
        $app = $this->loanApplication;

        $candidates = [
            ['label' => 'bank_statement', 'path' => $app->bank_statement_path],
            ['label' => 'live_photo', 'path' => $app->live_photo_path],
            ['label' => 'title_document', 'path' => $app->title_document],
            ['label' => 'upload_document', 'path' => $app->upload_document],
        ];

        foreach ($candidates as $row) {
            $full = $this->resolveDiskPath($row['path']);
            if ($full !== null) {
                $out[] = Attachment::fromPath($full)
                    ->as($row['label'] . '_' . basename($full));
            }
        }

        if ($app->relationLoaded('guarantor') && $app->guarantor && ! empty($app->guarantor->signed_form_path)) {
            $full = $this->resolveDiskPath($app->guarantor->signed_form_path);
            if ($full !== null) {
                $out[] = Attachment::fromPath($full)
                    ->as('guarantor_signed_form_' . basename($full));
            }
        }

        return $out;
    }

    protected function resolveDiskPath(?string $relative): ?string
    {
        if ($relative === null || $relative === '') {
            return null;
        }
        $relative = ltrim(str_replace('\\', '/', $relative), '/');

        $public = public_path($relative);
        if (is_file($public)) {
            return $public;
        }

        $storagePublic = storage_path('app/public/' . $relative);
        if (is_file($storagePublic)) {
            return $storagePublic;
        }

        return null;
    }
}

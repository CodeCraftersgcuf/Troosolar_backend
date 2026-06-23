<?php

namespace App\Mail;

use App\Models\LoanApplication;
use App\Services\PartnerLoanApplicationEmailPresenter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

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
    protected array $partnerEmailData = [];

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
        $this->partnerEmailData = $this->buildViewData($user, $loanApplication);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildViewData($user, LoanApplication $loanApplication): array
    {
        try {
            return (new PartnerLoanApplicationEmailPresenter($user, $loanApplication))->build();
        } catch (Throwable $e) {
            Log::warning('Partner email presenter fallback', [
                'loan_application_id' => $loanApplication->id,
                'message' => $e->getMessage(),
            ]);

            return [
                'is_bnpl' => is_array($loanApplication->loan_plan_snapshot) && $loanApplication->loan_plan_snapshot !== [],
                'is_manual_credit_check' => strtolower((string) ($loanApplication->credit_check_method ?? '')) === 'manual',
                'application' => [
                    'id' => $loanApplication->id,
                    'status' => $loanApplication->status,
                    'loan_amount' => $loanApplication->loan_amount,
                    'loan_amount_formatted' => $loanApplication->loan_amount
                        ? '₦'.number_format((float) $loanApplication->loan_amount, 2, '.', ',')
                        : null,
                    'repayment_duration' => $loanApplication->repayment_duration,
                ],
                'ordered_items' => ['lines' => [], 'display' => null],
                'customer' => [
                    'full_name' => trim(($user->first_name ?? '').' '.($user->sur_name ?? '')),
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
                'property' => [],
                'loan_summary' => null,
                'show_mono_section' => false,
                'mono_summary' => [],
                'credit_check' => [],
                'beneficiary' => [],
                'kyc_attachments' => [],
                'guarantor' => null,
                'admin' => [],
            ];
        }
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
        $fromAddress = trim((string) config('mail.from.address', ''));
        $fromName = (string) config('mail.from.name', 'Troosolar');
        $replyTo = filter_var($fromAddress, FILTER_VALIDATE_EMAIL)
            ? [new Address($fromAddress, $fromName)]
            : [];

        return new Envelope(
            subject: $this->emailSubject,
            replyTo: $replyTo,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.user_loan_info',
            with: [
                'viewData' => $this->partnerEmailData,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $out = [];
        $app = $this->loanApplication;

        if (! $app->relationLoaded('guarantor')) {
            $app->load('guarantor');
        }

        $candidates = [
            ['label' => 'bank_statement', 'path' => $app->bank_statement_path],
            ['label' => 'live_photo', 'path' => $app->live_photo_path],
            ['label' => 'title_document', 'path' => $app->title_document],
            ['label' => 'upload_document', 'path' => $app->upload_document],
        ];

        foreach ($candidates as $row) {
            try {
                $full = $this->resolveDiskPath($row['path']);
                if ($full !== null) {
                    $out[] = Attachment::fromPath($full)
                        ->as($row['label'].'_'.basename($full));
                }
            } catch (Throwable $e) {
                Log::warning('Skipped partner email attachment', [
                    'label' => $row['label'],
                    'path' => $row['path'],
                    'message' => $e->getMessage(),
                ]);
            }
        }

        if ($app->guarantor && ! empty($app->guarantor->signed_form_path)) {
            try {
                $full = $this->resolveDiskPath($app->guarantor->signed_form_path);
                if ($full !== null) {
                    $out[] = Attachment::fromPath($full)
                        ->as('guarantor_signed_form_'.basename($full));
                }
            } catch (Throwable $e) {
                Log::warning('Skipped guarantor attachment for partner email', [
                    'path' => $app->guarantor->signed_form_path,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $out;
    }

    protected function resolveDiskPath(?string $relative): ?string
    {
        if ($relative === null || trim($relative) === '') {
            return null;
        }

        $relative = ltrim(str_replace('\\', '/', trim($relative)), '/');
        if ($relative === '' || str_contains($relative, '..')) {
            return null;
        }

        $candidates = [
            public_path($relative),
            storage_path('app/public/'.$relative),
            storage_path('app/'.$relative),
        ];

        foreach ($candidates as $full) {
            if (is_file($full) && is_readable($full)) {
                return $full;
            }
        }

        return null;
    }
}

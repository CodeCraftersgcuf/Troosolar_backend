<?php

namespace App\Mail;

use App\Models\LoanApplication;
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

    /** @var array<int, string> */
    public array $loanPlanLines = [];

    public string $orderItemsSummary = '';

    /** @var array<int, string> */
    public array $monoSummaryLines = [];

    /** @var array<int, string> */
    public array $guarantorSummaryLines = [];

    /** @var array<int, string> */
    public array $finalApplicationPersonalLines = [];

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
        $this->loanPlanLines = $this->buildLoanPlanLines($loanApplication);
        $this->orderItemsSummary = $this->buildOrderItemsSummary($loanApplication);
        $this->monoSummaryLines = $this->buildMonoSummaryLines($loanApplication);
        $this->guarantorSummaryLines = $this->buildGuarantorSummaryLines($loanApplication);
        $this->finalApplicationPersonalLines = $this->buildFinalApplicationPersonalLines($loanApplication);
    }

    /**
     * @return array<int, string>
     */
    protected function buildFinalApplicationPersonalLines(LoanApplication $app): array
    {
        $snap = $app->loan_plan_snapshot;
        if (! is_array($snap) || empty($snap['final_application_personal']) || ! is_array($snap['final_application_personal'])) {
            return [];
        }

        $lines = [];
        foreach ($snap['final_application_personal'] as $key => $value) {
            if ($value !== null && $value !== '') {
                $lines[] = str_replace('_', ' ', (string) $key) . ': ' . $value;
            }
        }

        return $lines;
    }

    /**
     * @return array<int, string>
     */
    protected function buildLoanPlanLines(LoanApplication $app): array
    {
        $snap = $app->loan_plan_snapshot;
        if (! is_array($snap) || $snap === []) {
            return [];
        }

        $lines = [];
        $pairs = [
            'totalAmount' => 'Total amount (bundle + fees where stored)',
            'depositAmount' => 'Upfront due (deposit + admin fees)',
            'baseDepositAmount' => 'Equity deposit (before admin fees)',
            'totalLoanAmount' => 'Loan principal',
            'principal' => 'Principal',
            'totalInterestAmount' => 'Total interest',
            'totalInterest' => 'Total interest',
            'totalRepaymentAmount' => 'Total repayment',
            'totalRepayment' => 'Total repayment',
            'monthlyRepaymentAmount' => 'Monthly repayment',
            'monthlyRepayment' => 'Monthly repayment',
            'tenor' => 'Tenor (months)',
            'depositPercent' => 'Deposit %',
            'interestRate' => 'Interest rate % (monthly)',
            'insuranceFee' => 'Insurance fee',
            'managementFee' => 'Management fee',
            'legalFee' => 'Legal fee',
            'adminFeesTotal' => 'Total admin fees',
        ];

        foreach ($pairs as $key => $label) {
            if (array_key_exists($key, $snap) && $snap[$key] !== null && $snap[$key] !== '') {
                $lines[] = $label . ': ' . $snap[$key];
            }
        }

        return $lines;
    }

    protected function buildOrderItemsSummary(LoanApplication $app): string
    {
        $items = $app->order_items_snapshot;
        if (! is_array($items) || $items === []) {
            return '';
        }

        $lines = [];
        foreach ($items as $idx => $row) {
            if (! is_array($row)) {
                continue;
            }
            $type = $row['itemable_type'] ?? '';
            $id = $row['itemable_id'] ?? '';
            $qty = $row['quantity'] ?? 1;
            $unit = $row['unit_price'] ?? $row['subtotal'] ?? '';
            $lines[] = sprintf(
                'Item %d: type=%s id=%s qty=%s unit/subtotal=%s',
                $idx + 1,
                is_string($type) ? class_basename($type) : (string) $type,
                (string) $id,
                (string) $qty,
                (string) $unit
            );
        }

        return implode("\n", $lines);
    }

    /**
     * @return array<int, string>
     */
    protected function buildMonoSummaryLines(LoanApplication $app): array
    {
        $mono = $app->mono;
        if ($mono === null) {
            return [];
        }

        $lines = [];
        foreach ([
            'loan_amount' => 'Financed amount',
            'down_payment' => 'Down payment',
            'total_amount' => 'Total amount',
            'repayment_duration' => 'Repayment duration (mo)',
            'interest_rate' => 'Interest rate %',
            'credit_score' => 'Credit score',
            'status' => 'Status',
        ] as $attr => $label) {
            if (isset($mono->{$attr}) && $mono->{$attr} !== null && $mono->{$attr} !== '') {
                $lines[] = $label . ': ' . $mono->{$attr};
            }
        }

        return $lines;
    }

    /**
     * @return array<int, string>
     */
    protected function buildGuarantorSummaryLines(LoanApplication $app): array
    {
        $g = $app->guarantor;
        if ($g === null) {
            return [];
        }

        $lines = [];
        foreach ([
            'full_name' => 'Full name',
            'email' => 'Email',
            'phone' => 'Phone',
            'bvn' => 'BVN',
            'relationship' => 'Relationship',
            'status' => 'Status',
        ] as $attr => $label) {
            if (! empty($g->{$attr})) {
                $lines[] = $label . ': ' . $g->{$attr};
            }
        }

        return $lines;
    }

    protected function resolveSubject(): string
    {
        $snap = $this->loanApplication->loan_plan_snapshot ?? null;
        if (is_array($snap) && count($snap) > 0) {
            return 'BNPL Loan Application – Customer Info for Credit Evaluation.';
        }

        return 'Loan Application – Customer Info for Credit Evaluation.';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.user_loan_info',
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

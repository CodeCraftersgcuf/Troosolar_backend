<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\BnplSettings;
use App\Models\LoanApplication;
use App\Models\MonoCreditCheckSession;
use App\Models\MonoWebhookEvent;
use App\Models\User;
use App\Models\UserMonoAccount;
use App\Rules\BvnRule;
use App\Services\BnplLoanPlanCalculator;
use App\Services\MonoDocumentNormalizer;
use App\Services\MonoService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MonoAdminController extends Controller
{
    private function formatUserBrief($user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'sur_name' => $user->sur_name,
            'email' => $user->email,
            'phone' => $user->phone ?? $user->phone_number ?? null,
            'full_name' => trim(($user->first_name ?? '') . ' ' . ($user->sur_name ?? '')),
            'bvn' => $user->bvn && trim((string) $user->bvn) !== '' ? trim((string) $user->bvn) : null,
            'has_bvn' => $user->bvn && trim((string) $user->bvn) !== '',
        ];
    }

    /**
     * GET /api/admin/bnpl/mono/status
     */
    public function monoStatus(MonoService $monoService)
    {
        try {
            $public = $monoService->normalizeKey($monoService->getPublicKey());
            $secretInfo = $monoService->describeSecretKey();
            $auth = $monoService->verifyApiCredentials();

            return ResponseHelper::success([
                'mono_env' => $monoService->getEnv(),
                'public_key_prefix' => $public !== '' ? substr($public, 0, 10) . '...' : null,
                'secret_key' => $secretInfo,
                'api_auth' => $auth,
            ], $auth['ok'] ? 'Mono credentials look good.' : 'Mono credentials need attention.');
        } catch (Exception $e) {
            Log::error('Mono Admin monoStatus: ' . $e->getMessage());

            return ResponseHelper::error('Failed to check Mono status: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/admin/bnpl/mono/linked-accounts
     */
    public function linkedAccounts(Request $request)
    {
        try {
            $query = UserMonoAccount::with('user')->orderByDesc('linked_at')->orderByDesc('updated_at');

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('bank_name', 'like', "%{$search}%")
                        ->orWhere('account_name', 'like', "%{$search}%")
                        ->orWhere('mono_account_id', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($uq) use ($search) {
                            $uq->where('first_name', 'like', "%{$search}%")
                                ->orWhere('sur_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            }

            $accounts = $query->paginate($request->integer('per_page', 20));

            $accounts->getCollection()->transform(function (UserMonoAccount $row) {
                return [
                    'id' => $row->id,
                    'user_id' => $row->user_id,
                    'user' => $this->formatUserBrief($row->user),
                    'mono_account_id' => $row->mono_account_id,
                    'mono_customer_id' => $row->mono_customer_id,
                    'bank_name' => $row->bank_name,
                    'account_name' => $row->account_name,
                    'account_number_last4' => $row->account_number_last4,
                    'status' => $row->status,
                    'linked_at' => $row->linked_at,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                    'display_label' => $row->displayLabel(),
                    'is_linked' => $row->isLinked(),
                ];
            });

            return ResponseHelper::success($accounts, 'Linked Mono accounts retrieved successfully');
        } catch (Exception $e) {
            Log::error('Mono Admin linkedAccounts: ' . $e->getMessage());

            return ResponseHelper::error('Failed to retrieve linked Mono accounts', 500);
        }
    }

    /**
     * GET /api/admin/bnpl/mono/credit-sessions
     */
    public function creditSessions(Request $request)
    {
        try {
            $query = MonoCreditCheckSession::with(['user', 'loanApplication'])
                ->orderByDesc('created_at');

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('can_afford')) {
                $query->where('can_afford', filter_var($request->can_afford, FILTER_VALIDATE_BOOLEAN));
            }

            if ($request->filled('user_id')) {
                $query->where('user_id', (int) $request->user_id);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('bvn', 'like', "%{$search}%")
                        ->orWhere('mono_account_id', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($uq) use ($search) {
                            $uq->where('first_name', 'like', "%{$search}%")
                                ->orWhere('sur_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            }

            $sessions = $query->paginate($request->integer('per_page', 20));

            $sessions->getCollection()->transform(fn (MonoCreditCheckSession $s) => $this->formatCreditSessionSummary($s));

            return ResponseHelper::success($sessions, 'Mono credit check sessions retrieved successfully');
        } catch (Exception $e) {
            Log::error('Mono Admin creditSessions: ' . $e->getMessage());

            return ResponseHelper::error('Failed to retrieve Mono credit sessions', 500);
        }
    }

    /**
     * GET /api/admin/bnpl/mono/credit-sessions/{id}
     */
    public function showCreditSession($id)
    {
        try {
            $session = MonoCreditCheckSession::with(['user', 'loanApplication'])->find($id);

            if (! $session) {
                return ResponseHelper::error('Mono credit check session not found', 404);
            }

            $payload = $this->formatCreditSessionSummary($session);
            $payload['credit_worthiness_payload'] = $session->credit_worthiness_payload;
            $payload['error_message'] = $session->error_message;

            return ResponseHelper::success($payload, 'Mono credit check session retrieved successfully');
        } catch (Exception $e) {
            Log::error('Mono Admin showCreditSession: ' . $e->getMessage());

            return ResponseHelper::error('Failed to retrieve Mono credit check session', 500);
        }
    }

    /**
     * GET /api/admin/bnpl/mono/webhook-events
     */
    public function webhookEvents(Request $request)
    {
        try {
            $query = MonoWebhookEvent::orderByDesc('created_at');

            if ($request->filled('event')) {
                $query->where('event', $request->event);
            }

            if ($request->filled('mono_account_id')) {
                $query->where('mono_account_id', $request->mono_account_id);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('event', 'like', "%{$search}%")
                        ->orWhere('mono_account_id', 'like', "%{$search}%");
                });
            }

            $events = $query->paginate($request->integer('per_page', 20));

            $events->getCollection()->transform(function (MonoWebhookEvent $row) {
                return [
                    'id' => $row->id,
                    'event' => $row->event,
                    'mono_account_id' => $row->mono_account_id,
                    'payload_hash' => $row->payload_hash,
                    'payload' => $row->payload,
                    'processed_at' => $row->processed_at,
                    'created_at' => $row->created_at,
                ];
            });

            return ResponseHelper::success($events, 'Mono webhook events retrieved successfully');
        } catch (Exception $e) {
            Log::error('Mono Admin webhookEvents: ' . $e->getMessage());

            return ResponseHelper::error('Failed to retrieve Mono webhook events', 500);
        }
    }

    /**
     * POST /api/admin/bnpl/mono/users/{userId}/credit-check
     */
    public function runCreditCheck(Request $request, MonoService $monoService, int $userId)
    {
        try {
            User::findOrFail($userId);

            $linked = UserMonoAccount::where('user_id', $userId)
                ->where('status', 'linked')
                ->first();

            if (! $linked || ! $linked->mono_account_id) {
                return ResponseHelper::error('This user has no linked Mono bank account.', 422);
            }

            $data = $request->validate([
                'bvn' => 'nullable|string',
                'loan_amount' => 'nullable|numeric|min:0',
                'repayment_duration' => 'nullable|integer|min:1',
            ]);

            $loanApp = LoanApplication::where('user_id', $userId)->latest()->first();
            $user = User::find($userId);
            $settings = BnplSettings::get();

            $bvn = preg_replace('/\s+/', '', trim((string) (
                $data['bvn']
                ?? $loanApp?->bvn
                ?? $user?->bvn
                ?? ''
            )));

            if ($bvn === '') {
                return ResponseHelper::error('BVN is required. Set it on the customer profile or use Set BVN in Mono Loans.', 422);
            }

            if ($user && empty(trim((string) ($user->bvn ?? '')))) {
                $user->bvn = $bvn;
                $user->save();
            }

            $loanAmount = (float) (
                $data['loan_amount']
                ?? $loanApp?->loan_amount
                ?? $settings->minimum_loan_amount
                ?? 1500000
            );
            $termMonths = (int) (
                $data['repayment_duration']
                ?? $loanApp?->repayment_duration
                ?? 12
            );
            $principalKobo = (int) round($loanAmount * 100);

            $planSnapshot = is_array($loanApp?->loan_plan_snapshot)
                ? $loanApp->loan_plan_snapshot
                : null;
            $interestRate = BnplLoanPlanCalculator::interestMonthlyPercentFromSnapshot(
                $planSnapshot,
                (float) ($settings->interest_rate_percentage ?? 4)
            );

            $creditParams = [
                'bvn' => $bvn,
                'principal' => $principalKobo,
                'interest_rate' => $interestRate,
                'term' => $termMonths,
                'run_credit_check' => $monoService->shouldRunCreditCheck(),
            ];

            $requestAudit = $monoService->buildCreditWorthinessRequestAudit(
                $linked->mono_account_id,
                $creditParams
            );

            $session = MonoCreditCheckSession::create([
                'user_id' => $userId,
                'mono_account_id' => $linked->mono_account_id,
                'bvn' => $bvn,
                'principal_kobo' => $principalKobo,
                'interest_rate' => $interestRate,
                'term_months' => $termMonths,
                'run_credit_check' => $creditParams['run_credit_check'],
                'api_request_payload' => $requestAudit,
                'status' => 'pending',
                'loan_application_id' => $loanApp?->id,
            ]);

            $initResponse = $monoService->initiateCreditWorthiness($linked->mono_account_id, $creditParams);

            $session->update([
                'status' => 'processing',
                'api_init_response' => $initResponse,
            ]);

            return ResponseHelper::success([
                'session' => $this->formatCreditSessionSummary($session->fresh()),
                'message' => 'Credit check initiated. Results arrive via webhook; refresh Credit Sessions in a few moments.',
            ], 'Mono credit check started.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Mono Admin runCreditCheck: ' . $e->getMessage());

            return ResponseHelper::error('Failed to run Mono credit check: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/admin/bnpl/mono/users/{userId}/bvn
     */
    public function setUserBvn(Request $request, int $userId)
    {
        try {
            $user = User::findOrFail($userId);

            if ($user->bvn && trim((string) $user->bvn) !== '') {
                return ResponseHelper::error('Customer already has a BVN on file. It cannot be changed from here.', 422);
            }

            $data = $request->validate([
                'bvn' => ['required', 'string', new BvnRule()],
            ]);

            $bvn = preg_replace('/\s+/', '', trim((string) $data['bvn']));
            $user->bvn = $bvn;
            $user->save();

            return ResponseHelper::success([
                'user_id' => $userId,
                'bvn' => $bvn,
                'user' => $this->formatUserBrief($user->fresh()),
            ], 'BVN saved for customer.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Mono Admin setUserBvn: ' . $e->getMessage());

            return ResponseHelper::error('Failed to save BVN: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/admin/bnpl/mono/users/{userId}/documents
     */
    public function fetchUserDocuments(Request $request, MonoService $monoService, MonoDocumentNormalizer $normalizer, int $userId)
    {
        try {
            User::findOrFail($userId);

            $linked = UserMonoAccount::where('user_id', $userId)
                ->where('status', 'linked')
                ->first();

            if (! $linked || ! $linked->mono_account_id) {
                return ResponseHelper::error('This user has no linked Mono bank account.', 422);
            }

            $period = (string) $request->query('period', 'last6months');
            $accountId = $linked->mono_account_id;

            $documents = [
                'user_id' => $userId,
                'mono_account_id' => $accountId,
                'linked_account' => [
                    'bank_name' => $linked->bank_name,
                    'account_name' => $linked->account_name,
                    'account_number_last4' => $linked->account_number_last4,
                    'linked_at' => $linked->linked_at,
                ],
            ];

            $errors = [];

            foreach ([
                'account_details' => fn () => $monoService->getAccountDetails($accountId),
                'identity' => fn () => $monoService->getAccountIdentity($accountId),
                'balance' => fn () => $monoService->getAccountBalance($accountId),
                'statement_json' => fn () => $monoService->getAccountStatement($accountId, $period, 'json'),
            ] as $key => $callback) {
                try {
                    $documents[$key] = $callback();
                } catch (Exception $ex) {
                    $errors[$key] = $ex->getMessage();
                }
            }

            $documents['linked_account'] = $this->enrichLinkedAccountFromMono($linked, $documents);

            $latestSession = MonoCreditCheckSession::where('user_id', $userId)
                ->orderByDesc('created_at')
                ->first();

            if ($latestSession) {
                $documents['latest_credit_session'] = $this->formatCreditSessionSummary($latestSession);
                $documents['latest_credit_session']['credit_worthiness_payload'] = $latestSession->credit_worthiness_payload;
            }

            if ($errors !== []) {
                $documents['partial_errors'] = $errors;
            }

            $formatted = $normalizer->normalize($documents);

            return ResponseHelper::success($formatted, 'Mono documents fetched.');
        } catch (Exception $e) {
            Log::error('Mono Admin fetchUserDocuments: ' . $e->getMessage());

            return ResponseHelper::error('Failed to fetch Mono documents: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @param  array<string, mixed>  $documents
     * @return array<string, mixed>
     */
    private function enrichLinkedAccountFromMono(UserMonoAccount $linked, array $documents): array
    {
        $linkedAccount = is_array($documents['linked_account'] ?? null)
            ? $documents['linked_account']
            : [];

        if (! isset($documents['account_details']) || ! is_array($documents['account_details'])) {
            return $linkedAccount;
        }

        $data = $documents['account_details']['data'] ?? $documents['account_details'];
        if (! is_array($data)) {
            return $linkedAccount;
        }

        $account = is_array($data['account'] ?? null) ? $data['account'] : $data;
        $institution = is_array($account['institution'] ?? null) ? $account['institution'] : [];

        $bankName = (string) ($institution['name'] ?? $account['bank_name'] ?? '');
        $accountName = (string) ($account['name'] ?? $account['account_name'] ?? $account['accountName'] ?? '');
        $accountNumber = (string) ($account['account_number'] ?? $account['accountNumber'] ?? '');
        $lastFour = strlen($accountNumber) >= 4 ? substr($accountNumber, -4) : null;

        $updates = [];
        if ($bankName !== '' && empty($linked->bank_name)) {
            $updates['bank_name'] = $bankName;
        }
        if ($accountName !== '' && empty($linked->account_name)) {
            $updates['account_name'] = $accountName;
        }
        if ($lastFour && empty($linked->account_number_last4)) {
            $updates['account_number_last4'] = $lastFour;
        }

        if ($updates !== []) {
            $linked->update($updates);
            $linked->refresh();
        }

        return [
            'bank_name' => $linked->bank_name ?: ($linkedAccount['bank_name'] ?? null),
            'account_name' => $linked->account_name ?: ($linkedAccount['account_name'] ?? null),
            'account_number_last4' => $linked->account_number_last4 ?: ($linkedAccount['account_number_last4'] ?? null),
            'linked_at' => $linked->linked_at ?: ($linkedAccount['linked_at'] ?? null),
        ];
    }

    /**
     * POST /api/admin/bnpl/mono/users/{userId}/statement-pdf
     */
    public function fetchStatementPdf(Request $request, MonoService $monoService, int $userId)
    {
        try {
            User::findOrFail($userId);

            $linked = UserMonoAccount::where('user_id', $userId)
                ->where('status', 'linked')
                ->first();

            if (! $linked || ! $linked->mono_account_id) {
                return ResponseHelper::error('This user has no linked Mono bank account.', 422);
            }

            $data = $request->validate([
                'period' => 'nullable|string|max:32',
            ]);

            $period = $data['period'] ?? 'last6months';
            $result = $monoService->fetchStatementPdfUrl($linked->mono_account_id, $period);

            if ($result['download_url']) {
                return ResponseHelper::success([
                    'download_url' => $result['download_url'],
                    'job_id' => $result['job_id'],
                    'status' => $result['status'],
                    'period' => $period,
                ], 'Bank statement PDF is ready.');
            }

            return ResponseHelper::success([
                'download_url' => null,
                'job_id' => $result['job_id'],
                'status' => $result['status'],
                'period' => $period,
                'raw' => $result['raw'],
            ], 'Statement PDF is still processing. Try again in a few seconds.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Mono Admin fetchStatementPdf: ' . $e->getMessage());

            return ResponseHelper::error('Failed to fetch statement PDF: ' . $e->getMessage(), 500);
        }
    }

    private function formatCreditSessionSummary(MonoCreditCheckSession $session): array
    {
        $report = is_array($session->credit_worthiness_payload) ? $session->credit_worthiness_payload : [];

        return [
            'id' => $session->id,
            'user_id' => $session->user_id,
            'user' => $this->formatUserBrief($session->user),
            'loan_application_id' => $session->loan_application_id,
            'mono_account_id' => $session->mono_account_id,
            'mono_customer_id' => $session->mono_customer_id,
            'bvn' => $session->bvn,
            'principal_kobo' => $session->principal_kobo,
            'principal_naira' => $session->principal_kobo !== null ? round($session->principal_kobo / 100, 2) : null,
            'interest_rate' => $session->interest_rate,
            'term_months' => $session->term_months,
            'run_credit_check' => $session->run_credit_check,
            'status' => $session->status,
            'can_afford' => $session->can_afford,
            'monthly_payment_kobo' => $session->monthly_payment_kobo,
            'monthly_payment_naira' => $session->monthly_payment_kobo !== null
                ? round($session->monthly_payment_kobo / 100, 2)
                : null,
            'total_debt_kobo' => $report['debt']['total_debt'] ?? null,
            'total_debt_naira' => isset($report['debt']['total_debt'])
                ? round((float) $report['debt']['total_debt'] / 100, 2)
                : null,
            'has_full_report' => ! empty($report),
            'error_message' => $session->error_message,
            'api_request_payload' => $session->resolvedApiRequestPayload(),
            'api_init_response' => $session->resolvedApiInitResponse() ?? [
                'note' => 'Not stored for sessions before this update — run a new credit check.',
            ],
            'webhook_payload' => $report,
            'mono_result_message' => $report['message'] ?? $session->error_message,
            'created_at' => $session->created_at,
            'updated_at' => $session->updated_at,
        ];
    }
}

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\MonoCreditCheckSession;
use App\Models\MonoWebhookEvent;
use App\Models\UserMonoAccount;
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
        ];
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
            'created_at' => $session->created_at,
            'updated_at' => $session->updated_at,
        ];
    }
}

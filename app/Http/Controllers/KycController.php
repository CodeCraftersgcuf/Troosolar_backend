<?php

namespace App\Http\Controllers;

use App\Http\Requests\KycStoreRequest;
use App\Http\Requests\KycReviewRequest;
use App\Models\Kyc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class KycController extends Controller
{
    /** Helper: decide who is “admin” (tweak as needed) */
    protected function isReviewer($user): bool
    {
        // adjust the roles list to your app
        return in_array($user->role ?? null, ['admin','super_admin','compliance','kyc_reviewer']);
    }

    /**
     * POST /api/kyc
     * Auth user submits KYC (file upload)
     */
    public function store(KycStoreRequest $request)
    {
        $user = $request->user();

        // (Optional) only 1 pending at a time
        $existingPending = Kyc::where('user_id', $user->id)
            ->where('status', Kyc::STATUS_PENDING)
            ->first();

        if ($existingPending) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a pending KYC. Please wait for review.',
            ], 422);
        }

        $path = $request->file('file')->store('kyc', 'public');

        $kyc = Kyc::create([
            'user_id' => $user->id,
            'type'    => $request->string('type'),
            'file'    => $path,
            'status'  => Kyc::STATUS_PENDING,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'KYC submitted successfully.',
            'data'    => [
                'id'       => $kyc->id,
                'type'     => $kyc->type,
                'status'   => $kyc->status,
                'file_url' => asset('storage/'.$kyc->file),
                'created_at' => $kyc->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * GET /api/kyc/status
     * Auth user checks latest KYC status
     */
    public function myStatus(Request $request)
    {
        $kyc = Kyc::where('user_id', $request->user()->id)
            ->latest('id')
            ->first();

        if (!$kyc) {
            return response()->json([
                'success' => true,
                'message' => 'No KYC found for this user.',
                'data'    => null,
            ], 200);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'           => $kyc->id,
                'type'         => $kyc->type,
                'status'       => $kyc->status,
                'file_url'     => asset('storage/'.$kyc->file),
                'reviewed_by'  => $kyc->reviewed_by,
                'reviewed_at'  => $kyc->reviewed_at?->toIso8601String(),
                'review_notes' => $kyc->review_notes,
                'created_at'   => $kyc->created_at?->toIso8601String(),
                'updated_at'   => $kyc->updated_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * GET /api/admin/kyc?status=pending  (optional listing)
     */
    public function index(Request $request)
    {
        if (!$this->isReviewer($request->user())) {
            return response()->json(['success'=>false,'message'=>'Forbidden'], 403);
        }

        $q = Kyc::query()->with('user');
        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }

        $items = $q->orderByDesc('id')->paginate($request->integer('per_page', 15));

        // Return plain JSON (no Resource)
        $data = $items->through(function ($k) {
            return [
                'id'           => $k->id,
                'user_id'      => $k->user_id,
                'type'         => $k->type,
                'status'       => $k->status,
                'file_url'     => asset('storage/'.$k->file),
                'reviewed_by'  => $k->reviewed_by,
                'reviewed_at'  => $k->reviewed_at?->toIso8601String(),
                'review_notes' => $k->review_notes,
                'created_at'   => $k->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $data->items(),
            'meta'    => [
                'current_page' => $items->currentPage(),
                'per_page'     => $items->perPage(),
                'total'        => $items->total(),
                'last_page'    => $items->lastPage(),
            ]
        ]);
    }

    /**
     * POST /api/admin/kyc/{kyc}/review
     * Body: { action: "approve"|"reject", review_notes?: string }
     */
    public function review(KycReviewRequest $request, Kyc $kyc)
    {
        if (!$this->isReviewer($request->user())) {
            return response()->json(['success'=>false,'message'=>'Forbidden'], 403);
        }

        if ($kyc->status !== Kyc::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending KYCs can be reviewed.',
            ], 422);
        }

        $action = $request->string('action');

        DB::transaction(function () use ($request, $kyc, $action) {
            $kyc->status       = $action === 'approve' ? Kyc::STATUS_APPROVED : Kyc::STATUS_REJECTED;
            $kyc->reviewed_by  = $request->user()->id;
            $kyc->reviewed_at  = now();
            $kyc->review_notes = $request->input('review_notes');
            $kyc->save();
        });

        return response()->json([
            'success' => true,
            'message' => $action === 'approve' ? 'KYC approved.' : 'KYC rejected.',
            'data'    => [
                'id'           => $kyc->id,
                'status'       => $kyc->status,
                'reviewed_by'  => $kyc->reviewed_by,
                'reviewed_at'  => $kyc->reviewed_at?->toIso8601String(),
                'review_notes' => $kyc->review_notes,
            ],
        ]);
    }

    /**
     * POST /api/kyc/{kyc}/replace-file   (optional)
     * Only owner & only while pending
     */
    public function replaceFile(KycStoreRequest $request, Kyc $kyc)
    {
        if ($kyc->user_id !== $request->user()->id) {
            return response()->json(['success'=>false,'message'=>'Forbidden'], 403);
        }

        if ($kyc->status !== Kyc::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'You can only replace files for pending KYCs.',
            ], 422);
        }

        $new = $request->file('file')->store('kyc', 'public');

        if ($kyc->file && Storage::disk('public')->exists($kyc->file)) {
            Storage::disk('public')->delete($kyc->file);
        }

        $kyc->update(['file' => $new]);

        return response()->json([
            'success' => true,
            'message' => 'File replaced successfully.',
            'data'    => [
                'id'       => $kyc->id,
                'file_url' => asset('storage/'.$kyc->file),
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLinkAccountRequest;
use App\Http\Requests\UpdateLinkAccountRequest;
use App\Models\LinkAccount;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use Exception;
use Illuminate\Support\Facades\Log;

class LinkAccountController extends Controller
{
    public function index()
    {
        try {
            $accounts = LinkAccount::where('user_id', auth()->id())->get();
            return ResponseHelper::success('Accounts fetched successfully', $accounts);
        } catch (Exception $e) {
            Log::error("Index Error: " . $e->getMessage());
            return ResponseHelper::error('Failed to fetch accounts', 500);
        }
    }

    public function store(StoreLinkAccountRequest $request)
    {
        try {
            $data = $request->validated();
            $data['user_id'] = auth()->id();
            $account = LinkAccount::create($data);
            return ResponseHelper::success('Account created successfully', $account);
        } catch (Exception $e) {
            Log::error("Store Error: " . $e->getMessage());
            return ResponseHelper::error('Failed to store account', 500);
        }
    }

    public function show($id)
    {
        try {
            $account = LinkAccount::where('user_id', auth()->id())->findOrFail($id);
            return ResponseHelper::success('Account fetched successfully', $account);
        } catch (Exception $e) {
            Log::error("Show Error: " . $e->getMessage());
            return ResponseHelper::error('Account not found', 404);
        }
    }

    public function update(UpdateLinkAccountRequest $request, $id)
    {
        try { 
            $data = $request->validated();
            $account = LinkAccount::where('user_id', auth()->id())->findOrFail($id);
            $account->update($data);
            return ResponseHelper::success('Account updated successfully', $account);
        } catch (Exception $e) {
            Log::error("Update Error: " . $e->getMessage());
            return ResponseHelper::error('Failed to update account', 500);
        }
    }

    public function destroy($id)
    {
        try {
            $account = LinkAccount::where('user_id', auth()->id())->findOrFail($id);
            $account->delete();
            return ResponseHelper::success('Account deleted successfully');
        } catch (Exception $e) {
            Log::error("Delete Error: " . $e->getMessage());
            return ResponseHelper::error('Failed to delete account', 500);
        }
    }
}
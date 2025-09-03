<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreditDataRequest;
use App\Models\CreditData;
use Exception;
use Illuminate\Http\Request;

class CreditDataController extends Controller
{
    public function addCreditData(CreditDataRequest $request)
    {
        try
        {
            $data = $request->validated();
            $addCredit = CreditData::create($data);
            return ResponseHelper::success($addCredit, 'Add credit data successfully');
        }
        catch(Exception $ex)
        {
            return ResponseHelper::error('Credit data is not added ');
        }
    }
}

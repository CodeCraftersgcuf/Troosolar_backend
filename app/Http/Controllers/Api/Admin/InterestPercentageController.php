<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\InterestPercentageRequest;
use App\Models\InterestPercentage;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InterestPercentageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try{
            $interestPercentage = InterestPercentage::all();
            return ResponseHelper::success($interestPercentage, 'All Interest Percentages');
        }
        catch(Exception $ex){
            return ResponseHelper::error("Not fetch the Interest percentages");
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(InterestPercentageRequest $request)
    {
                try{
            $data = $request->validated();
            $interestPercentage =  InterestPercentage::create($data);
            Log::info("created succesfully");
            return ResponseHelper::success($interestPercentage, 'Interest Percentage is created successfully');

        }
        catch(Exception $e){
            Log::error("interest percentage is not created". $e->getMessage());
            return ResponseHelper::error("Interest Percentage is not created");
    }
}
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
         try{
            $interestPercentage = InterestPercentage::where('id', $id)->get();
            return ResponseHelper::success($interestPercentage, 'All Interest Percentages');
        }
        catch(Exception $ex){
            return ResponseHelper::error("Not fetch the Interest percentages");
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(InterestPercentageRequest $request, string $id)
    {
         try{
            $interestPercentageId = InterestPercentage::findorfail( $id);
            $data = $request->validated();
            $interestPercentage = InterestPercentage::where('id', $id)->update($data);
            return ResponseHelper::success($interestPercentage, 'Update Interest Percentages');
        }
        catch(Exception $ex){
            Log::error('not creared'. $ex->getMessage());
            return ResponseHelper::error("Not update the Interest percentages");
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
         try{
            $interestPercentageId = InterestPercentage::findorfail($id);
            if($interestPercentageId){
               $interestPercentage =  InterestPercentage::where('id', $id)->delete();
            }
            return ResponseHelper::success($interestPercentage, 'Delete Interest Percentages');
        }
        catch(Exception $ex){
             Log::error('not creared'. $ex->getMessage());
            return ResponseHelper::error("Not delete the Interest percentages");
        }
    }
}

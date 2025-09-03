<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\TermRequest;
use App\Models\Term;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TermController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try{
            $terms = Term::all();
            return ResponseHelper::success($terms, 'All the terms');
        }
        catch(Exception $ex)
    {
        return ResponseHelper::error('Something is wrong');
    }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TermRequest $request)
    {
        try{
            $data = $request->validated();
            $terms = Term::create($data);
            return ResponseHelper::success($terms, "Term is add succesfully");
        }
        catch(Exception $ex)
    {
        return ResponseHelper::error('Term is not add');
    }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $type)
    {
          try{
            $terms = Term::where('type', $type)->first();
            return ResponseHelper::success($terms, "Single Term ");
        }
        catch(Exception $ex)
    {
        return ResponseHelper::error('Term is not fetch');
    }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
          try{
            $data = $request->all();
            // dd($data);
            // $terms = Term::where('id', $id)->update($data);
            $terms = Term::findOrFail($id);
$terms->update($data);
            Log::info('term is updated');
            return ResponseHelper::success($terms, "Term is updated succesfully");
        }
        catch(Exception $ex)
    {
        Log::error('term is not updated'.$ex->getMessage());
        return ResponseHelper::error('Term is not update');
    }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
          try{
            $terms = Term::where('id', $id)->delete();
            return ResponseHelper::success($terms, "Term is deleted succesfully");
        }
        catch(Exception $ex)
    {
        return ResponseHelper::error('Term is not delete');
    }
    }
}

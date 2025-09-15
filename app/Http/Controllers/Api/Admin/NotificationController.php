<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Http\Requests\NotificationRequest;

class NotificationController extends Controller
{
    public function index()
    {
        try {
            $notifications = Notification::latest()->get();

            return response()->json([
                'status' => 'success',
                'data'   => $notifications
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function userNotifications(){
        try{
            $systemNotifications=Notification::where('type','system')->get();
            $userNotifications=Notification::where('user_id',auth()->user()->id)->get();
            $notifications=array_merge($systemNotifications->toArray(),$userNotifications->toArray());
            return ResponseHelper::success($notifications);

        }catch(\Exception $e){
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function store(NotificationRequest $request)
    {
        try {
            $notification = Notification::create([
                'message' => $request->message
            ]);

            return response()->json([
                'status' => 'success',
                'data'   => $notification
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $notification = Notification::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data'   => $notification
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    public function update(NotificationRequest $request, $id)
    {
        try {
            $notification = Notification::findOrFail($id);

            $notification->update([
                'message' => $request->message
            ]);

            return response()->json([
                'status' => 'success',
                'data'   => $notification
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $notification = Notification::findOrFail($id);
            $notification->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Notification deleted'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 404);
        }
    }
}

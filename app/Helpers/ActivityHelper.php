<?php 

namespace App\Helpers;

use App\Models\UserActivity;

class ActivityHelper{

   public static function saveActivity($user_id, $message){
    $activity = new UserActivity();          // $activity is now a model instance
    $activity->user_id = $user_id;
    $activity->activity = $message;         // ← you put the model into itself
    $activity->save();
    return $activity;
}

}
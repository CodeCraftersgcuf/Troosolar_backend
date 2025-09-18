<?php 

namespace App\Helpers;

use App\Models\UserActivity;

class ActivityHelper{

    public static function saveActivity($user_id, $activity){
        $activity = new UserActivity();
        $activity->user_id = $user_id;
        $activity->activity = $activity;
        $activity->save();
        return $activity;
    }
}
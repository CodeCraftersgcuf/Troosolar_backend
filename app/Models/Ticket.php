<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'subject', 'status'];

    // ✅ Define relationship to User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ✅ Define relationship to Messages
    public function messages()
    {
        return $this->hasMany(TicketMessage::class);
    }
}

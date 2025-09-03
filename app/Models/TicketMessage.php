<?php

// app/Models/TicketMessage.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketMessage extends Model
{
    use HasFactory;

protected $fillable = ['ticket_id', 'user_id', 'message', 'sender'];

public function user()
{
    return $this->belongsTo(User::class);
}
}



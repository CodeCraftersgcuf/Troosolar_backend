<?php

namespace App\Http\Controllers\Api\Website;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\TicketRequest;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{

public function index()
{
    try {
        $user = Auth::user();

        if (!$user) {
            return ResponseHelper::error('Unauthorized', 401);
        }

        $tickets = Ticket::with(['messages' => function ($query) {
                $query->orderBy('created_at');
            }])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return ResponseHelper::success($tickets, 'Tickets retrieved successfully.');
    } catch (\Exception $e) {
        return ResponseHelper::error('Failed to fetch tickets: ' . $e->getMessage(), 500);
    }
}

    public function store(TicketRequest $request)
    {
        try {
            $user = Auth::user() ?? \App\Models\User::first(); // fallback for local testing

            $userId = $user?->id;
            $username = $user?->name;

            // Create the ticket
            $ticket = Ticket::create([
                'user_id' => $userId,
                'subject' => $request->subject,
                'status'  => 'pending',
            ]);

            // User message
            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id'   => $userId,
                'message'   => $request->message,
                'sender'    => 'user',
            ]);

            // Auto-reply by admin
            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id'   => null,
                'message'   => 'Your message has been received. Our support team will get back to you shortly.',
                'sender'    => 'admin',
            ]);

            return ResponseHelper::success([
                'ticket_id' => $ticket->id,
                'subject'   => $ticket->subject,
                'user_name' => User::find($ticket->user_id)->first_name ,
                'date'      => $ticket->created_at->format('Y-m-d H:i:s'),
                'messages'  => $ticket->messages()->get(['sender', 'message', 'created_at']),
            ], 'Ticket created and acknowledged');

        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to create ticket: ' . $e->getMessage(), 500);
        }
    }
}
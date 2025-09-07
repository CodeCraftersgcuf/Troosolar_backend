<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminReplyTicketRequest;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;

class AdminTicketController extends Controller
{
    // ✅ List all tickets
    public function index()
    {
        try {
            $tickets = Ticket::with('user')
                ->latest()
                ->get()
                ->map(function ($ticket) {
                    return [
                        'ticket_id' => $ticket->id,
                        'user_name' => User::find($ticket->user_id)->first_name ,
                        'subject'   => $ticket->subject,
                        'status'    => $ticket->status,
                        'date'      => $ticket->created_at->format('Y-m-d H:i:s'),
                    ];
                });

            // Add summary counts
            $totalTickets   = Ticket::count();
            $pendingTickets = Ticket::where('status', 'pending')->count();
            $answeredTickets = Ticket::where('status', 'answered')->count();

            return ResponseHelper::success([
                'summary' => [
                    'total_tickets'    => $totalTickets,
                    'pending_tickets'  => $pendingTickets,
                    'answered_tickets' => $answeredTickets,
                ],
                'tickets' => $tickets,
            ], 'All tickets retrieved');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to fetch tickets: ' . $e->getMessage(), 500);
        }
    }

    // ✅ Show single ticket
    public function show($id)
    {
        try {
            $ticket = Ticket::with(['user', 'messages'])->findOrFail($id);

            return ResponseHelper::success([
                'ticket_id' => $ticket->id,
                'subject'   => $ticket->subject,
                'user_name' => $ticket->user?->name ?? 'Unknown',
                'status'    => $ticket->status,
                'date'      => $ticket->created_at->format('Y-m-d H:i:s'),
                'messages'  => $ticket->messages->map(function ($msg) {
                    return [
                        'sender'  => $msg->sender,
                        'message' => $msg->message,
                        'date'    => $msg->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
            ], 'Ticket details retrieved');
        } catch (\Exception $e) {
            return ResponseHelper::error('Ticket not found: ' . $e->getMessage(), 404);
        }
    }

    // ✅ Admin reply to a ticket (uses form request like TicketController)
    public function reply(AdminReplyTicketRequest $request, $ticket_id)
    {
        try {
            $ticket = Ticket::findOrFail($ticket_id);

            // Admin reply message
            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id'   => null,
                'message'   => $request->message,
                'sender'    => 'admin',
            ]);

            // Update ticket status
            $ticket->update(['status' => 'answered']);

            return ResponseHelper::success([
                'ticket_id' => $ticket->id,
                'status'    => $ticket->status,
                'reply'     => $request->message,
                'date'      => now()->format('Y-m-d H:i:s'),
            ], 'Reply sent successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to send reply: ' . $e->getMessage(), 500);
        }
    }
}

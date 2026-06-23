<?php

namespace App\Http\Controllers\Api\Website;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\TicketSubject;

class TicketSubjectController extends Controller
{
    /**
     * Active ticket subjects for the support form dropdown.
     * GET /api/site/ticket-subjects
     */
    public function index()
    {
        $subjects = TicketSubject::query()
            ->active()
            ->ordered()
            ->get(['id', 'title', 'sort_order']);

        return ResponseHelper::success($subjects, 'Ticket subjects retrieved successfully');
    }
}

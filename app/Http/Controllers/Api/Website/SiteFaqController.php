<?php

namespace App\Http\Controllers\Api\Website;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\SiteFaq;

class SiteFaqController extends Controller
{
    /**
     * Public FAQs for customer dashboard.
     * GET /api/site/faqs
     */
    public function index()
    {
        $faqs = SiteFaq::query()
            ->active()
            ->ordered()
            ->get(['id', 'question', 'answer', 'sort_order']);

        return ResponseHelper::success($faqs, 'FAQs retrieved successfully');
    }
}

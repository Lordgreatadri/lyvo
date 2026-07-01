<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OperatorVerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\OperatorProfile;
use App\Services\OperatorReviewService;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * OperatorApprovalController
 * --------------------------
 * Admin verification center actions. Moves operators through the approval
 * workflow (via OperatorReviewService, which records every transition in the
 * audit trail). Operators are resolved by uuid (never the auto-increment id).
 */
class OperatorApprovalController extends Controller
{
    public function __construct(private readonly OperatorReviewService $review)
    {
    }

    public function index(): View
    {
        return view('admin.operators.index', [
            'pending' => OperatorProfile::with('user', 'category', 'media')
                ->whereIn('verification_status', [
                    OperatorVerificationStatus::Pending->value,
                    OperatorVerificationStatus::InReview->value,
                ])
                ->latest()
                ->get(),
        ]);
    }

    public function show(OperatorProfile $operator): View
    {
        $operator->load('user', 'category', 'media', 'verificationEvents.actor');

        return view('admin.operators.show', ['operator' => $operator]);
    }

    public function markInReview(Request $request, OperatorProfile $operator): RedirectResponse
    {
        $this->authorize(Permissions::VERIFICATION_REVIEW);

        $this->review->markInReview($operator, $request->user());

        return back()->with('status', 'Operator marked as under review.');
    }

    public function approve(Request $request, OperatorProfile $operator): RedirectResponse
    {
        $this->authorize(Permissions::VERIFICATION_APPROVE);

        $this->review->approve($operator, $request->user());

        return back()->with('status', 'Operator approved. Dashboard unlocked.');
    }

    public function reject(Request $request, OperatorProfile $operator): RedirectResponse
    {
        $this->authorize(Permissions::VERIFICATION_REJECT);

        $validated = $request->validate(['rejection_reason' => ['required', 'string', 'max:1000']]);

        $this->review->reject($operator, $request->user(), $validated['rejection_reason']);

        return back()->with('status', 'Operator application rejected.');
    }
}

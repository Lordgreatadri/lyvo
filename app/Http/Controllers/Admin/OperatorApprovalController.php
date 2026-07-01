<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OperatorVerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\OperatorProfile;
use App\Models\OperatorVerificationEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * OperatorApprovalController
 * --------------------------
 * Admin verification center actions. Moves operators through the approval
 * workflow and records every transition in the audit trail. Operators are
 * resolved by uuid (never the auto-increment id).
 */
class OperatorApprovalController extends Controller
{
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
        $this->transition($operator, OperatorVerificationStatus::InReview, $request->user()->id);

        return back()->with('status', 'Operator marked as under review.');
    }

    public function approve(Request $request, OperatorProfile $operator): RedirectResponse
    {
        DB::transaction(function () use ($operator, $request) {
            $from = $operator->verification_status->value;

            $operator->update([
                'verification_status' => OperatorVerificationStatus::Approved,
                'approved_at' => now(),
                'approved_by' => $request->user()->id,
                'rejection_reason' => null,
            ]);

            $this->logEvent($operator, $from, OperatorVerificationStatus::Approved->value, $request->user()->id, 'Operator approved.');
        });

        return back()->with('status', 'Operator approved. Dashboard unlocked.');
    }

    public function reject(Request $request, OperatorProfile $operator): RedirectResponse
    {
        $request->validate(['rejection_reason' => ['required', 'string', 'max:1000']]);

        DB::transaction(function () use ($operator, $request) {
            $from = $operator->verification_status->value;

            $operator->update([
                'verification_status' => OperatorVerificationStatus::Rejected,
                'rejection_reason' => $request->input('rejection_reason'),
                'approved_at' => null,
                'approved_by' => null,
            ]);

            $this->logEvent($operator, $from, OperatorVerificationStatus::Rejected->value, $request->user()->id, $request->input('rejection_reason'));
        });

        return back()->with('status', 'Operator application rejected.');
    }

    private function transition(OperatorProfile $operator, OperatorVerificationStatus $to, int $actorId): void
    {
        $from = $operator->verification_status->value;
        $operator->update(['verification_status' => $to]);
        $this->logEvent($operator, $from, $to->value, $actorId, null);
    }

    private function logEvent(OperatorProfile $operator, ?string $from, string $to, int $actorId, ?string $notes): void
    {
        OperatorVerificationEvent::create([
            'operator_profile_id' => $operator->id,
            'actor_id' => $actorId,
            'from_status' => $from,
            'to_status' => $to,
            'notes' => $notes,
        ]);
    }
}

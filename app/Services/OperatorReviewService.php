<?php

namespace App\Services;

use App\Enums\OperatorVerificationStatus;
use App\Models\OperatorProfile;
use App\Models\OperatorVerificationEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * OperatorReviewService
 * ---------------------
 * Encapsulates the operator verification state machine and its audit trail so
 * both the admin verification centre and the user-management screen drive the
 * workflow through one consistent, transactional path.
 */
class OperatorReviewService
{
    public function markInReview(OperatorProfile $operator, User $actor): void
    {
        $this->transition($operator, OperatorVerificationStatus::InReview, $actor, null, null);
    }

    public function approve(OperatorProfile $operator, User $actor): void
    {
        $this->transition(
            $operator,
            OperatorVerificationStatus::Approved,
            $actor,
            [
                'approved_at' => now(),
                'approved_by' => $actor->id,
                'rejection_reason' => null,
            ],
            'Operator approved.',
        );
    }

    public function reject(OperatorProfile $operator, User $actor, string $reason): void
    {
        $this->transition(
            $operator,
            OperatorVerificationStatus::Rejected,
            $actor,
            [
                'rejection_reason' => $reason,
                'approved_at' => null,
                'approved_by' => null,
            ],
            $reason,
        );
    }

    /**
     * Apply a status change plus optional column updates and log the event.
     *
     * @param  array<string, mixed>|null  $extra
     */
    private function transition(
        OperatorProfile $operator,
        OperatorVerificationStatus $to,
        User $actor,
        ?array $extra,
        ?string $notes,
    ): void {
        DB::transaction(function () use ($operator, $to, $actor, $extra, $notes) {
            $from = $operator->verification_status->value;

            $operator->update(array_merge(['verification_status' => $to], $extra ?? []));

            OperatorVerificationEvent::create([
                'operator_profile_id' => $operator->id,
                'actor_id' => $actor->id,
                'from_status' => $from,
                'to_status' => $to->value,
                'notes' => $notes,
            ]);
        });
    }
}

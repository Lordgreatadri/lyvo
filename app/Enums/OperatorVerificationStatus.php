<?php

namespace App\Enums;

/**
 * OperatorVerificationStatus
 * --------------------------
 * Tracks an operator business through the admin approval workflow. An operator
 * may authenticate at any status, but the operator dashboard is only unlocked
 * once the status is Approved (enforced by the EnsureOperatorApproved middleware).
 */
enum OperatorVerificationStatus: string
{
    case Pending = 'pending';       // Submitted, awaiting documents / review queue
    case InReview = 'in_review';    // An admin is actively reviewing
    case Approved = 'approved';     // Fully verified — dashboard & public listing unlocked
    case Rejected = 'rejected';     // Declined — operator may resubmit

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending Review',
            self::InReview => 'Under Review',
            self::Approved => 'Verified Operator',
            self::Rejected => 'Rejected',
        };
    }

    public function isApproved(): bool
    {
        return $this === self::Approved;
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::InReview => 'sky',
            self::Approved => 'emerald',
            self::Rejected => 'rose',
        };
    }
}

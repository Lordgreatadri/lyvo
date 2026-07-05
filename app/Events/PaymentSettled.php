<?php

namespace App\Events;

use App\Models\PaymentTransaction;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * PaymentSettled
 * --------------
 * Fired by the (provider-agnostic) PaymentService whenever a transaction reaches
 * a terminal state (successful or failed). This is the seam that lets other
 * domains react to money movement without the Payment domain depending on them —
 * e.g. the escrow listener advances a paid order to "funds held".
 */
class PaymentSettled
{
    use Dispatchable;

    public function __construct(public PaymentTransaction $transaction)
    {
    }
}

<?php

namespace App\Http\Controllers;

use App\Support\DemoData;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EscrowController extends Controller
{
    /**
     * Escrow transactions overview.
     */
    public function index(): View
    {
        return view('escrow.index', [
            'transactions' => DemoData::escrowTransactions(),
        ]);
    }

    /**
     * Single escrow transaction, resolved by UUID (not auto-increment PK).
     */
    public function show(string $transaction): View
    {
        $record = collect(DemoData::escrowTransactions())
            ->firstWhere('uuid', $transaction);

        if (! $record) {
            throw new NotFoundHttpException('Escrow transaction not found.');
        }

        return view('escrow.show', [
            'transaction' => $record,
            'pipeline'    => DemoData::escrowPipeline(),
        ]);
    }
}

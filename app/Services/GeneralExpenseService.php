<?php

namespace App\Services;

use App\Models\GeneralExpenseLine;
use App\Models\GeneralExpensePayment;

class GeneralExpenseService
{
    public function computeTotals(int $expenseId): array
    {
        $lines = GeneralExpenseLine::query()->where('general_expense_id', $expenseId);

        return [
            'expense_total' => (float) $lines->sum('total_amount'),
            'taxable_total' => (float) $lines->sum('taxable_amount'),
            'gst_total' => (float) $lines->sum('gst_amount'),
        ];
    }

    public function computePayments(int $expenseId): float
    {
        return (float) GeneralExpensePayment::query()
            ->where('general_expense_id', $expenseId)
            ->sum('amount');
    }

    public function computeBalance(int $expenseId): float
    {
        $totals = $this->computeTotals($expenseId);
        $paidTotal = $this->computePayments($expenseId);

        return $totals['expense_total'] - $paidTotal;
    }
}

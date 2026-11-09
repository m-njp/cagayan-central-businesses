<?php

namespace App\Enums;

enum FinanceBasis: string
{
    case Actual = 'actual';
    case BudgetFinal = 'budget_final';
}

<?php

namespace App\Enums;

enum FinanceDocumentKind: string
{
    case ExecutiveSummary = 'executive_summary';
    case FsWorkbook = 'fs_workbook';
    case AnnexWorkbook = 'annex_workbook';

    public function label(): string
    {
        return match ($this) {
            self::ExecutiveSummary => 'Executive Summary',
            self::FsWorkbook => 'Financial Statements',
            self::AnnexWorkbook => 'General Fund Annex',
        };
    }
}

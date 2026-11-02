<?php

namespace App\Enums;

enum FinanceUploadStatus: string
{
    case Queued = 'queued';
    case Running = 'running';
    case Done = 'done';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'Waiting',
            self::Running => 'Reading',
            self::Done => 'Read',
            self::Failed => 'Could Not Read',
        };
    }
}

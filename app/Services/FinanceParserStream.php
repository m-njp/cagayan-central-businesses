<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;

final class FinanceParserStream
{
    private string $buffer = '';

    /** @var array<string, mixed>|null */
    public ?array $result = null;

    public function __construct(private readonly \Closure $onEvent) {}

    public function push(string $chunk): void
    {
        $this->buffer .= $chunk;
        while (($position = strpos($this->buffer, "\n")) !== false) {
            $line = substr($this->buffer, 0, $position);
            $this->buffer = substr($this->buffer, $position + 1);
            $this->read($line);
        }
        if (strlen($this->buffer) > (int) config('cagayan.finances.uploads.progress_line_bytes_max')) {
            throw new \RuntimeException('Parser output exceeded its configured limit.');
        }
    }

    public function finish(): void
    {
        if (trim($this->buffer) !== '') {
            $this->read($this->buffer);
            $this->buffer = '';
        }
        if ($this->result === null) {
            throw new \RuntimeException('Parser did not return a result.');
        }
    }

    private function read(string $line): void
    {
        if (trim($line) === '') {
            return;
        }
        if (strlen($line) > (int) config('cagayan.finances.uploads.progress_line_bytes_max') || $this->result !== null) {
            throw new \RuntimeException('Invalid parser event sequence.');
        }
        $event = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($event) || ! is_string($event['type'] ?? null)) {
            throw new \RuntimeException('Invalid parser event.');
        }
        if ($event['type'] === 'result') {
            $result = $event['result'] ?? null;
            if (! is_array($result) || ! is_string($result['parser_version'] ?? null)
                || ! is_array($result['figures'] ?? null) || ! is_array($result['issues'] ?? null) || ! is_array($result['children'] ?? null)) {
                throw new \RuntimeException('Invalid parser result.');
            }
            $this->result = $result;
            Validator::make($result, [
                'figures' => ['array', 'list'], 'figures.*' => ['array'],
                'figures.*.line_code' => ['required', 'string'],
                'figures.*.fiscal_year' => ['required', 'integer'],
                'figures.*.basis' => ['required', 'in:actual,budget_final'],
                'figures.*.value' => ['required', 'string', 'regex:/^-?\d+\.\d{2}$/D'],
                'figures.*.document_kind' => ['required', 'in:executive_summary,fs_workbook,annex_workbook'],
                'issues' => ['array', 'list'], 'issues.*' => ['array'],
                'children' => ['array', 'list'], 'children.*' => ['array'],
                'children.*.kind' => ['required', 'in:fs_workbook,annex_workbook'],
                'children.*.name' => ['required', 'in:fs_workbook.xlsx,annex_workbook.xlsx'],
                'children.*.sha256' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/D'],
            ])->validate();
        } else {
            ($this->onEvent)($event);
        }
    }
}

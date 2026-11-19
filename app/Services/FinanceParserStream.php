<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;

final class FinanceParserStream
{
    private string $buffer = '';

    /** @var array<string, mixed>|null */
    public ?array $result = null;

    public function __construct(
        private readonly \Closure $onEvent,
        private readonly ?int $reportYear = null,
        private readonly ?string $documentKind = null,
        private readonly ?string $sourceHash = null,
    ) {}

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
                || ! is_array($result['figures'] ?? null) || ! is_array($result['issues'] ?? null)) {
                throw new \RuntimeException('Invalid parser result.');
            }
            Validator::make($result, [
                'source_sha256' => ['sometimes', 'string', 'regex:/^[a-f0-9]{64}$/D'],
                'confidence' => ['sometimes', 'integer', 'between:0,100'],
                'figures' => ['array', 'list'], 'figures.*' => ['array'],
                'figures.*.line_code' => ['required', 'string'],
                'figures.*.fiscal_year' => ['required', 'integer'],
                'figures.*.basis' => ['required', 'in:actual,budget_final'],
                'figures.*.value' => ['required', 'string', 'regex:/^-?\d+\.\d{2}$/D'],
                'figures.*.document_kind' => ['required', 'in:executive_summary'],
                'figures.*.location' => ['required', 'string', 'max:'.config('cagayan.finances.location_max')],
                'issues' => ['array', 'list'], 'issues.*' => ['array'],
                'issues.*.severity' => ['required', 'in:info,warning,error'],
                'issues.*.kind' => ['required', 'string'],
                'issues.*.code' => ['sometimes', 'string'],
                'issues.*.message' => ['required', 'string'],
                'issues.*.document_kind' => ['sometimes', 'in:executive_summary'],
                'issues.*.document_sha256' => ['sometimes', 'string', 'regex:/^[a-f0-9]{64}$/D'],
            ])->validate();
            $this->validateContext($result);
            $this->result = $result;
        } else {
            ($this->onEvent)($event);
        }
    }

    /** @param array<string, mixed> $result */
    private function validateContext(array $result): void
    {
        if (isset($result['confidence']) && ! is_int($result['confidence'])) {
            throw new \RuntimeException('Parser returned an invalid confidence score.');
        }
        if (isset($result['source_sha256']) && $this->sourceHash !== null && ! hash_equals($this->sourceHash, (string) $result['source_sha256'])) {
            throw new \RuntimeException('Parser result does not match the stored source checksum.');
        }
        /** @var list<array{document_kind: string, line_code: string, fiscal_year: mixed, basis: string, location: string}> $figures */
        $figures = $result['figures'];
        $seen = [];
        foreach ($figures as $figure) {
            $year = $figure['fiscal_year'];
            if (! is_int($year) || ($this->reportYear !== null && ! in_array($year, [$this->reportYear, $this->reportYear - 1], true))) {
                throw new \RuntimeException('Parser returned a fiscal year outside the report context.');
            }
            $kind = $figure['document_kind'];
            if ($this->documentKind !== null && $kind !== $this->documentKind) {
                throw new \RuntimeException('Parser returned a figure for another document kind.');
            }
            if ($kind === 'executive_summary' && ! preg_match('/^page [1-9][0-9]*$/D', $figure['location'])) {
                throw new \RuntimeException('Parser returned a PDF figure without a positive source page.');
            }
            $key = implode(' ', [$kind, $figure['line_code'], (string) $year, $figure['basis']]);
            if (isset($seen[$key])) {
                throw new \RuntimeException('Parser returned duplicate financial observations.');
            }
            $seen[$key] = true;
        }

        /** @var list<array{document_kind?: string, document_sha256?: string}> $issues */
        $issues = $result['issues'];
        foreach ($issues as $issue) {
            if (! isset($issue['document_kind'])) {
                if (isset($issue['document_sha256'])) {
                    throw new \RuntimeException('Parser returned a checksum without its document kind.');
                }

                continue;
            }
            $kind = $issue['document_kind'];
            if ($this->documentKind !== null && $kind !== $this->documentKind) {
                throw new \RuntimeException('Parser returned an issue for another document kind.');
            }
            if (isset($issue['document_sha256']) && $this->sourceHash !== null && ! hash_equals($this->sourceHash, $issue['document_sha256'])) {
                throw new \RuntimeException('Parser issue does not match the stored source checksum.');
            }
        }
    }
}

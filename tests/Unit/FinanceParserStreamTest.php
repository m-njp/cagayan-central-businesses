<?php

use App\Services\FinanceParserStream;

it('delivers fragmented events before the final result and accepts a final unterminated line', function () {
    $events = [];
    $stream = new FinanceParserStream(function (array $event) use (&$events): void {
        $events[] = $event;
    });
    $stream->push('{"type":"sta');
    expect($events)->toBe([]);
    $stream->push('ge","stage":"scanning"}'."\n");
    expect($events)->toBe([['type' => 'stage', 'stage' => 'scanning']]);
    expect($stream->result)->toBeNull();
    $stream->push(json_encode(['type' => 'result', 'result' => ['parser_version' => '1', 'figures' => [], 'issues' => [], 'children' => []]], JSON_THROW_ON_ERROR));
    $stream->finish();
    expect($stream->result['figures'])->toBe([]);
});

it('refuses a missing result malformed output and events after completion', function (string $output) {
    $stream = new FinanceParserStream(fn (array $event) => null);
    expect(function () use ($stream, $output): void {
        $stream->push($output);
        $stream->finish();
    })->toThrow(Exception::class);
})->with([
    'missing result' => "{\"type\":\"stage\",\"stage\":\"scanning\"}\n",
    'malformed event' => "not json\n",
    'invalid result' => "{\"type\":\"result\",\"result\":{}}\n",
    'event after result' => "{\"type\":\"result\",\"result\":{\"parser_version\":\"1\",\"figures\":[],\"issues\":[],\"children\":[]}}\n{\"type\":\"stage\"}\n",
]);

it('limits incomplete output instead of buffering it indefinitely', function () {
    config(['cagayan.finances.uploads.progress_line_bytes_max' => 20]);
    $stream = new FinanceParserStream(fn (array $event) => null);
    expect(fn () => $stream->push(str_repeat('x', 21)))->toThrow(RuntimeException::class);
});

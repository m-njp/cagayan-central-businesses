import assert from 'node:assert/strict';
import { test } from 'node:test';
import {
    trendOf,
    trendWord,
    CHANGE_WORDS,
} from '../../resources/js/lib/trend.ts';

void test('a rise is a green up triangle and a fall a red down triangle', () => {
    assert.deepEqual(trendOf('275749956.23'), {
        direction: 1,
        tone: 'text-trend-up',
        glyph: '▲',
    });
    assert.deepEqual(trendOf('-34917500.01'), {
        direction: -1,
        tone: 'text-trend-down',
        glyph: '▼',
    });
    assert.equal(trendOf(0).glyph, '=');
});

void test('a missing or invalid figure has no trend, so it never looks like a rise or a fall', () => {
    for (const value of [null, undefined, '', 'abc'])
        assert.equal(trendOf(value), null);
});

void test('a trend is worded as a change', () => {
    assert.equal(trendWord(trendOf(5), CHANGE_WORDS), 'Increased');
    assert.equal(trendWord(trendOf(-5), CHANGE_WORDS), 'Decreased');
    assert.equal(trendWord(trendOf(0), CHANGE_WORDS), 'Unchanged');
});

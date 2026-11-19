<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('tests run against the PostGIS test database', function () {
    expect(DB::connection()->getDriverName())->toBe('pgsql')
        // Parallel runs add a worker suffix to the database name.
        ->and(DB::connection()->getDatabaseName())->toStartWith('cagayan_business_test');

    $version = DB::scalar('SELECT PostGIS_Version()');

    expect($version)->toBeString()->toStartWith('3.');
});

test('a point is found inside a polygon that contains it', function () {
    // A small square around Tuguegarao City centre, in longitude and latitude (SRID 4326).
    $square = 'POLYGON((121.72 17.60, 121.75 17.60, 121.75 17.63, 121.72 17.63, 121.72 17.60))';

    $inside = DB::scalar(
        'SELECT ST_Contains(ST_GeomFromText(?, 4326), ST_SetSRID(ST_MakePoint(?, ?), 4326))',
        [$square, 121.73, 17.61],
    );

    $outside = DB::scalar(
        'SELECT ST_Contains(ST_GeomFromText(?, 4326), ST_SetSRID(ST_MakePoint(?, ?), 4326))',
        [$square, 121.80, 17.61],
    );

    expect($inside)->toBeTrue()
        ->and($outside)->toBeFalse();
});

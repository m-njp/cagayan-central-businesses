<?php

use Illuminate\Support\Facades\File;

// D20. Built JavaScript is public, so the login path must never be compiled into it.
// Runs after `npm run build` (the pre-push hook builds first). Skipped when there is no build.

test('the built assets do not contain the login path', function () {
    $assets = public_path('build/assets');

    if (! File::isDirectory($assets)) {
        $this->markTestSkipped('No production build. Run npm run build first.');
    }

    $path = config('cagayan.admin.login_path');

    foreach (File::allFiles($assets) as $file) {
        expect(str_contains($file->getContents(), $path))
            ->toBeFalse("The login path appears in {$file->getFilename()}");
    }
});

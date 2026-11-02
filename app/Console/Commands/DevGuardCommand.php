<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Runs before `composer dev`. Several chats each starting their own dev server left three servers fighting over
 * port 8000 and a stale public/hot pointing at a dead Vite, so every page went blank (26-10-29).
 */
#[Signature('dev:guard {--port=8000 : The port php artisan serve uses}')]
#[Description('Refuse to start a second dev server, and clear a stale Vite hot file')]
class DevGuardCommand extends Command
{
    public function handle(): int
    {
        $port = (int) $this->option('port');
        if ($this->answers('127.0.0.1', $port)) {
            $this->components->error("A dev server is already running at http://localhost:{$port}. Use it, or stop it before running composer dev again.");

            return self::FAILURE;
        }

        $hot = public_path('hot');
        if (is_file($hot)) {
            $url = parse_url(trim((string) file_get_contents($hot)));
            if (! $this->answers(trim($url['host'] ?? '', '[]'), (int) ($url['port'] ?? 0))) {
                unlink($hot);
                $this->components->info('Removed a stale public/hot left by a Vite server that is no longer running.');
            }
        }

        return self::SUCCESS;
    }

    private function answers(string $host, int $port): bool
    {
        if ($host === '' || $port === 0) {
            return false;
        }
        $socket = @fsockopen($host, $port, $errorCode, $errorMessage, 1);
        if ($socket === false) {
            return false;
        }
        fclose($socket);

        return true;
    }
}

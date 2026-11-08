<?php

it('refuses to start a second dev server on a port that already answers', function () {
    $server = stream_socket_server('tcp://127.0.0.1:0');
    $port = (int) substr(strrchr(stream_socket_get_name($server, false), ':'), 1);

    $this->artisan('dev:guard', ['--port' => $port])->assertFailed();

    fclose($server);
});

it('removes a hot file that points at a Vite server that is gone', function () {
    $hot = public_path('hot');
    $existing = is_file($hot) ? file_get_contents($hot) : null;
    $closed = stream_socket_server('tcp://127.0.0.1:0');
    $deadPort = (int) substr(strrchr(stream_socket_get_name($closed, false), ':'), 1);
    fclose($closed);
    file_put_contents($hot, "http://[::1]:{$deadPort}");

    $free = stream_socket_server('tcp://127.0.0.1:0');
    $port = (int) substr(strrchr(stream_socket_get_name($free, false), ':'), 1);
    fclose($free);
    $this->artisan('dev:guard', ['--port' => $port])->assertSuccessful();

    expect(is_file($hot))->toBeFalse();
    if ($existing !== null) {
        file_put_contents($hot, $existing);
    }
});

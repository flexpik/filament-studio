<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Operations\Communication\HttpRequestActivity;
use Illuminate\Support\Facades\Http;

it('performs a GET and returns parsed JSON', function () {
    Http::fake(['example.com/*' => Http::response(['ok' => true], 200, ['Content-Type' => 'application/json'])]);

    $ctx = makeOperationContext(config: ['method' => 'GET', 'url' => 'https://example.com/x']);
    $result = (new HttpRequestActivity)->execute($ctx);

    expect($result->output())->toMatchArray(['status' => 200]);
    expect($result->output()['body'])->toBe(['ok' => true]);
});

it('routes 5xx to failure branch by default', function () {
    Http::fake(['*' => Http::response('boom', 500)]);

    $ctx = makeOperationContext(config: ['method' => 'GET', 'url' => 'https://example.com/x']);
    $result = (new HttpRequestActivity)->execute($ctx);

    expect($result->output()['status'])->toBe(500);
    expect($result->branch())->toBe('failure');
});

it('treats 5xx as success when fail_on_error=false', function () {
    Http::fake(['*' => Http::response('boom', 500)]);

    $ctx = makeOperationContext(config: ['method' => 'GET', 'url' => 'https://example.com/x', 'fail_on_error' => false]);
    $result = (new HttpRequestActivity)->execute($ctx);

    expect($result->branch())->toBeNull();
});

it('sends headers and body', function () {
    Http::fake();

    $ctx = makeOperationContext(config: [
        'method' => 'POST',
        'url' => 'https://example.com/api',
        'headers' => ['X-Token' => 'abc'],
        'body' => ['hello' => 'world'],
    ]);

    (new HttpRequestActivity)->execute($ctx);

    Http::assertSent(fn ($req) => $req->hasHeader('X-Token', 'abc') && $req['hello'] === 'world');
});

<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Mail\FlowGenericMail;
use Flexpik\FilamentStudio\Flows\Operations\Communication\SendEmailActivity;
use Illuminate\Support\Facades\Mail;

beforeEach(fn () => Mail::fake());

it('dispatches a FlowGenericMail to the configured recipient', function () {
    $ctx = makeOperationContext(config: ['to' => 'sera@example.com', 'subject' => 'Hi', 'body' => '<p>Hello</p>']);
    $result = (new SendEmailActivity)->execute($ctx);

    Mail::assertSent(FlowGenericMail::class, fn ($mail) => $mail->hasTo('sera@example.com'));
    expect($result->output())->toMatchArray(['sent' => true, 'to' => 'sera@example.com']);
});

it('supports cc and reply_to', function () {
    $ctx = makeOperationContext(config: ['to' => 'a@x.com', 'subject' => 's', 'body' => 'b', 'cc' => 'c@x.com', 'reply_to' => 'r@x.com']);
    (new SendEmailActivity)->execute($ctx);

    Mail::assertSent(FlowGenericMail::class, fn ($mail) => $mail->hasCc('c@x.com'));
});

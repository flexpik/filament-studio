<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Operations\Communication;

use Flexpik\FilamentStudio\Contracts\Flows\FlowOperation;
use Flexpik\FilamentStudio\Contracts\Flows\OperationContext;
use Flexpik\FilamentStudio\Contracts\Flows\OperationResult;
use Flexpik\FilamentStudio\Flows\Mail\FlowGenericMail;
use Illuminate\Support\Facades\Mail;

class SendEmailActivity implements FlowOperation
{
    public function execute(OperationContext $context): OperationResult
    {
        $config = $context->config();
        $to = (string) $config['to'];
        $mail = new FlowGenericMail((string) $config['subject'], (string) $config['body']);

        if (isset($config['from'])) {
            $mail->from($config['from']);
        }
        if (isset($config['reply_to'])) {
            $mail->replyTo($config['reply_to']);
        }

        $pending = Mail::to($to);
        if (isset($config['cc'])) {
            $pending->cc($config['cc']);
        }
        $pending->send($mail);

        return OperationResult::success(['sent' => true, 'to' => $to]);
    }
}

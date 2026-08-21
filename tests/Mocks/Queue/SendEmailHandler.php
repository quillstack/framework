<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Mocks\Queue;

use Quillstack\Queue\Handler;

class SendEmailHandler implements Handler
{
    public function __construct(private readonly SentEmails $sent)
    {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function handle(object $message): void
    {
        if ($message instanceof SendEmail) {
            $this->sent->addresses[] = $message->email;
        }
    }
}

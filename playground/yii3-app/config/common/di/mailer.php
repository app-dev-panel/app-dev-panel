<?php

declare(strict_types=1);

use Yiisoft\Aliases\Aliases;
use Yiisoft\Mailer\FileMailer;
use Yiisoft\Mailer\MailerInterface;

return [
    MailerInterface::class => static fn(Aliases $aliases): MailerInterface => new FileMailer($aliases->get(
        '@runtime/mail',
    )),
];

<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use yii\base\Action;

final class MailerAction extends Action
{
    public function run(): array
    {
        // Send a real email via Yii's mailer — the Module hooks into
        // BaseMailer::EVENT_AFTER_SEND and feeds email metadata to MailerCollector.
        $mailer = \Yii::$app->mailer;
        $message = $mailer
            ->compose()
            ->setFrom('noreply@example.com')
            ->setTo('user@example.com')
            ->setSubject('ADP Test Fixture Email')
            ->setTextBody('This is a test email from the ADP mailer fixture.')
            ->setHtmlBody('<p>This is a test email from the ADP mailer fixture.</p>');

        $message->send();

        return ['fixture' => 'mailer:basic', 'status' => 'ok'];
    }
}

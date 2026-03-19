<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use AppDevPanel\Kernel\Collector\MailerCollector;
use yii\base\Action;

final class MailerAction extends Action
{
    public function run(): array
    {
        /** @var \AppDevPanel\Adapter\Yii2\Module $module */
        $module = \Yii::$app->getModule('debug-panel');

        /** @var MailerCollector|null $mailerCollector */
        $mailerCollector = $module->getCollector(MailerCollector::class);

        if ($mailerCollector === null) {
            return ['fixture' => 'mailer:basic', 'status' => 'error', 'message' => 'MailerCollector not found'];
        }

        // Simulate a sent email by calling the collector directly.
        // This tests the MailerCollector without requiring a real mailer component.
        $mailerCollector->collectMessage([
            'from' => ['noreply@example.com' => 'ADP Test'],
            'to' => ['user@example.com' => 'Test User'],
            'cc' => [],
            'bcc' => [],
            'replyTo' => [],
            'subject' => 'ADP Test Fixture Email',
            'textBody' => 'This is a test email from the ADP mailer fixture.',
            'htmlBody' => '<p>This is a test email from the ADP mailer fixture.</p>',
            'raw' => '',
            'charset' => 'utf-8',
            'date' => date('r'),
        ]);

        return ['fixture' => 'mailer:basic', 'status' => 'ok'];
    }
}

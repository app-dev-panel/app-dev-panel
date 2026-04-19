<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use yii\base\Action;

final class MailerAction extends Action
{
    public function run(): array
    {
        $mailer = \Yii::$app->mailer;

        $plain = $mailer
            ->compose()
            ->setFrom('noreply@example.com')
            ->setTo('user@example.com')
            ->setSubject('ADP fixture — plain text')
            ->setTextBody("Hello!\n\nThis is a plain-text email sent from the ADP Yii 2 fixture.\n\nCheers,\nADP");

        $table = $mailer
            ->compose()
            ->setFrom('noreply@example.com')
            ->setTo('user@example.com')
            ->setSubject('ADP fixture — HTML table report')
            ->setTextBody("Weekly report:\n- Requests: 1240\n- Errors: 12\n- Avg response: 84ms")
            ->setHtmlBody(MailerFixtureContent::tableHtml());

        $rich = $mailer
            ->compose()
            ->setFrom('noreply@example.com')
            ->setTo('user@example.com')
            ->setSubject('ADP fixture — newsletter with attachments')
            ->setTextBody('Please see the attached TXT and PDF files.')
            ->setHtmlBody(MailerFixtureContent::newsletterHtml())
            ->attachContent(
                MailerFixtureContent::textAttachment(),
                ['fileName' => 'release-notes.txt', 'contentType' => 'text/plain'],
            )
            ->attachContent(
                MailerFixtureContent::pdfAttachment(),
                ['fileName' => 'adp-fixture.pdf', 'contentType' => 'application/pdf'],
            );

        $plain->send();
        $table->send();
        $rich->send();

        return ['fixture' => 'mailer:basic', 'status' => 'ok', 'sent' => 3];
    }
}

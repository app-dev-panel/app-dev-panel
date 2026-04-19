<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use Illuminate\Http\JsonResponse;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;

final class MailerAction
{
    public function __invoke(): JsonResponse
    {
        Mail::raw(
            "Hello!\n\nThis is a plain-text email sent from the ADP Laravel fixture.\n\nCheers,\nADP",
            static function (Message $message): void {
                $message
                    ->from('noreply@example.com', 'ADP')
                    ->to('user@example.com', 'Test User')
                    ->subject('ADP fixture — plain text');
            },
        );

        Mail::html(
            MailerFixtureContent::tableHtml(),
            static function (Message $message): void {
                $message
                    ->from('noreply@example.com', 'ADP')
                    ->to('user@example.com', 'Test User')
                    ->subject('ADP fixture — HTML table report');
            },
        );

        Mail::html(
            MailerFixtureContent::newsletterHtml(),
            static function (Message $message): void {
                $message
                    ->from('noreply@example.com', 'ADP')
                    ->to('user@example.com', 'Test User')
                    ->subject('ADP fixture — newsletter with attachments')
                    ->attachData(
                        MailerFixtureContent::textAttachment(),
                        'release-notes.txt',
                        ['mime' => 'text/plain'],
                    )
                    ->attachData(
                        MailerFixtureContent::pdfAttachment(),
                        'adp-fixture.pdf',
                        ['mime' => 'application/pdf'],
                    );
            },
        );

        return new JsonResponse(['fixture' => 'mailer:basic', 'status' => 'ok', 'sent' => 3]);
    }
}

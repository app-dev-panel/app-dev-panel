<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

final class MailerAction
{
    public function __invoke(): JsonResponse
    {
        Mail::raw('This is a test email from the ADP mailer fixture.', static function ($message): void {
            $message
                ->from('noreply@example.com', 'ADP Test')
                ->to('user@example.com', 'Test User')
                ->subject('ADP Test Fixture Email');
        });

        return new JsonResponse(['fixture' => 'mailer:basic', 'status' => 'ok']);
    }
}

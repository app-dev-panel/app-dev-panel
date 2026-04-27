<?php

declare(strict_types=1);

namespace App\Controller\Web;

use App\Application\Layout;
use AppDevPanel\Kernel\Collector\ValidatorCollector;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ContactPage
{
    public function __construct(
        private readonly Layout $layout,
        private readonly ValidatorCollector $validator,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $alert = '';
        $name = '';
        $email = '';
        $message = '';

        if ($request->getMethod() === 'POST') {
            $parsed = $request->getParsedBody();
            $data = is_array($parsed) ? $parsed : [];
            $name = (string) ($data['name'] ?? '');
            $email = (string) ($data['email'] ?? '');
            $message = (string) ($data['message'] ?? '');

            $errors = [];
            if ($name === '') {
                $errors['name'] = 'Name is required';
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Valid email required';
            }
            if (strlen($message) < 10) {
                $errors['message'] = 'Message must be at least 10 characters';
            }

            $this->validator->collect(
                ['name' => $name, 'email' => $email, 'message' => $message],
                $errors === [],
                $errors,
                ['name' => ['required'], 'email' => ['email'], 'message' => ['min:10']],
            );

            $alert = $errors === []
                ? '<div class="alert alert-success">Thanks! Your message has been recorded.</div>'
                : sprintf(
                    '<div class="alert alert-error">Please fix the errors below: %s</div>',
                    htmlspecialchars(implode(', ', $errors)),
                );
        }

        $nameEsc = htmlspecialchars($name);
        $emailEsc = htmlspecialchars($email);
        $messageEsc = htmlspecialchars($message);

        $content = <<<HTML
            <div class="page-header">
                <h1>Contact Form</h1>
                <p>Submit a form with server-side validation — results feed the validator collector.</p>
            </div>
            <div class="card">
                {$alert}
                <form method="POST" action="/contact">
                    <div class="form-group">
                        <label>Name</label>
                        <input class="form-control" name="name" value="{$nameEsc}" />
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input class="form-control" name="email" value="{$emailEsc}" />
                    </div>
                    <div class="form-group">
                        <label>Message</label>
                        <textarea class="form-control" name="message" rows="4">{$messageEsc}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Send</button>
                </form>
            </div>
            HTML;

        return new Response(200)
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withBody(Stream::create($this->layout->render('Contact', $content, '/contact')));
    }
}

<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Tests\E2E;

use GuzzleHttp\Client;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * E2E tests for playground HTML pages and API endpoints.
 *
 * Verifies that all playground pages render correctly with expected HTML elements,
 * forms work with validation, and JSON API endpoints return proper responses.
 *
 * Run: PLAYGROUND_URL=http://127.0.0.1:8102 php vendor/bin/phpunit --testsuite Fixtures --group pages
 */
#[Group('fixtures')]
#[Group('pages')]
final class PlaygroundPagesTest extends TestCase
{
    private static Client $client;
    private static string $baseUrl;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$baseUrl = rtrim((string) getenv('PLAYGROUND_URL') ?: 'http://127.0.0.1:8080', '/');
        self::$client = new Client([
            'base_uri' => self::$baseUrl,
            'http_errors' => false,
            'timeout' => 5,
        ]);

        try {
            $response = self::$client->get('/');
            if ($response->getStatusCode() === 0) {
                self::fail(sprintf('Playground server is not running. Start it and re-run. URL: %s', self::$baseUrl));
            }
        } catch (\Throwable) {
            self::fail(sprintf('Playground server is not running. Start it and re-run. URL: %s', self::$baseUrl));
        }
    }

    // ── Home page ──

    public function testHomePageReturnsHtml(): void
    {
        $response = self::$client->get('/');
        $body = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('<!DOCTYPE html>', $body);
        self::assertStringContainsString('ADP Playground', $body);
    }

    public function testHomePageHasNavigation(): void
    {
        $body = (string) self::$client->get('/')->getBody();

        self::assertStringContainsString('Home', $body);
        self::assertStringContainsString('Users', $body);
        self::assertStringContainsString('Contact', $body);
        self::assertStringContainsString('API Playground', $body);
        self::assertStringContainsString('Error Demo', $body);
    }

    public function testHomePageHasFeatureCards(): void
    {
        $body = (string) self::$client->get('/')->getBody();

        self::assertStringContainsString('feature-card', $body);
        self::assertStringContainsString('href="/users"', $body);
        self::assertStringContainsString('href="/contact"', $body);
        self::assertStringContainsString('href="/api-playground"', $body);
        self::assertStringContainsString('/debug/api/', $body);
    }

    public function testHomePageHasFooter(): void
    {
        $body = (string) self::$client->get('/')->getBody();

        self::assertStringContainsString('class="footer"', $body);
        self::assertStringContainsString('Application Development Panel', $body);
    }

    // ── Users page ──

    public function testUsersPageReturnsHtml(): void
    {
        $response = self::$client->get('/users');
        $body = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('<table', $body);
    }

    public function testUsersPageHasTableHeaders(): void
    {
        $body = (string) self::$client->get('/users')->getBody();

        self::assertStringContainsString('<th', $body);
        self::assertStringContainsString('Name', $body);
        self::assertStringContainsString('Email', $body);
        self::assertStringContainsString('Role', $body);
    }

    public function testUsersPageHasUserData(): void
    {
        $body = (string) self::$client->get('/users')->getBody();

        self::assertStringContainsString('Alice', $body);
        self::assertStringContainsString('alice@example.com', $body);
        self::assertStringContainsString('Bob', $body);
        self::assertStringContainsString('bob@example.com', $body);
        self::assertStringContainsString('Charlie', $body);
        self::assertStringContainsString('charlie@example.com', $body);
    }

    public function testUsersPageHasRoleBadges(): void
    {
        $body = (string) self::$client->get('/users')->getBody();

        self::assertStringContainsString('badge', $body);
        self::assertStringContainsString('Admin', $body);
    }

    // ── Contact page ──

    public function testContactPageReturnsHtml(): void
    {
        $response = self::$client->get('/contact');
        $body = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('<form', $body);
    }

    public function testContactPageHasFormFields(): void
    {
        $body = (string) self::$client->get('/contact')->getBody();

        self::assertStringContainsString('name="name"', $body);
        self::assertStringContainsString('name="email"', $body);
        self::assertStringContainsString('name="message"', $body);
        self::assertStringContainsString('type="submit"', $body);
    }

    public function testContactFormValidationShowsErrors(): void
    {
        $response = $this->submitContactForm([
            'name' => '',
            'email' => '',
            'message' => '',
        ]);
        $body = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('form-error', $body);
    }

    public function testContactFormValidationRejectsInvalidEmail(): void
    {
        $response = $this->submitContactForm([
            'name' => 'Test User',
            'email' => 'not-an-email',
            'message' => 'Hello',
        ]);
        $body = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('form-error', $body);
    }

    public function testContactFormSuccessfulSubmission(): void
    {
        $response = $this->submitContactForm([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'message' => 'Hello from E2E test',
        ]);
        $body = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('alert-success', $body);
    }

    // ── API Playground page ──

    public function testApiPlaygroundPageReturnsHtml(): void
    {
        $response = self::$client->get('/api-playground');
        $body = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('API Playground', $body);
    }

    public function testApiPlaygroundHasControls(): void
    {
        $body = (string) self::$client->get('/api-playground')->getBody();

        self::assertStringContainsString('<select', $body);
        self::assertStringContainsString('/api', $body);
        self::assertStringContainsString('/api/users', $body);
        self::assertStringContainsString('/api/error', $body);
        self::assertStringContainsString('send', strtolower($body));
    }

    public function testApiPlaygroundHasJavaScript(): void
    {
        $body = (string) self::$client->get('/api-playground')->getBody();

        self::assertStringContainsString('<script>', $body);
        self::assertStringContainsString('fetch(', $body);
    }

    // ── Error Demo page ──

    public function testErrorDemoReturnsServerError(): void
    {
        $response = self::$client->get('/error');

        self::assertGreaterThanOrEqual(400, $response->getStatusCode());
    }

    // ── API endpoints (JSON) ──

    public function testApiIndexReturnsJson(): void
    {
        // Try /api first, fall back to /api/ (Yii3 uses trailing slash)
        $response = self::$client->get('/api');
        if ($response->getStatusCode() === 404) {
            $response = self::$client->get('/api/');
        }
        $body = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());

        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertArrayHasKey('message', $data);
        self::assertStringContainsString('ADP', $data['message']);
        self::assertArrayHasKey('endpoints', $data);
    }

    public function testApiUsersReturnsJson(): void
    {
        $response = self::$client->get('/api/users');
        $body = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());

        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertArrayHasKey('users', $data);
        self::assertCount(3, $data['users']);

        $alice = $data['users'][0];
        self::assertSame(1, $alice['id']);
        self::assertSame('Alice', $alice['name']);
        self::assertSame('alice@example.com', $alice['email']);
    }

    public function testApiErrorThrowsException(): void
    {
        $response = self::$client->get('/api/error');

        self::assertGreaterThanOrEqual(400, $response->getStatusCode());
    }

    // ── CSS Design System ──

    public function testPagesUseSharedDesignSystem(): void
    {
        $body = (string) self::$client->get('/')->getBody();

        self::assertStringContainsString('--color-primary', $body);
        self::assertStringContainsString('--color-header', $body);
        self::assertStringContainsString('class="header"', $body);
        self::assertStringContainsString('class="main"', $body);
    }

    // ── Navigation consistency ──

    /**
     * @return array<string, array{string}>
     */
    public static function pageUrlProvider(): array
    {
        return [
            'home' => ['/'],
            'users' => ['/users'],
            'contact' => ['/contact'],
            'api-playground' => ['/api-playground'],
        ];
    }

    #[DataProvider('pageUrlProvider')]
    public function testAllPagesHaveConsistentLayout(string $url): void
    {
        $body = (string) self::$client->get($url)->getBody();

        self::assertStringContainsString('class="header"', $body);
        self::assertStringContainsString('class="main"', $body);
        self::assertStringContainsString('class="footer"', $body);
        self::assertStringContainsString('ADP Playground', $body);
    }

    #[DataProvider('pageUrlProvider')]
    public function testAllPagesHaveNavLinks(string $url): void
    {
        $body = (string) self::$client->get($url)->getBody();

        self::assertStringContainsString('href="/"', $body);
        self::assertStringContainsString('href="/users"', $body);
        self::assertStringContainsString('href="/contact"', $body);
        self::assertStringContainsString('href="/api-playground"', $body);
    }

    // ── Helpers ──

    /**
     * Submit the contact form with proper CSRF token and session handling.
     *
     * Uses a dedicated client with cookie jar to maintain session between
     * GET (fetch CSRF token) and POST (submit form).
     *
     * @param array<string, string> $formData
     */
    private function submitContactForm(array $formData): \Psr\Http\Message\ResponseInterface
    {
        $jar = new \GuzzleHttp\Cookie\CookieJar();
        $client = new Client([
            'base_uri' => self::$baseUrl,
            'http_errors' => false,
            'timeout' => 5,
            'cookies' => $jar,
        ]);

        // GET the form page to extract CSRF token and establish session
        $html = (string) $client->get('/contact')->getBody();

        // Extract hidden input name/value pairs (CSRF tokens)
        if (preg_match_all(
            '/<input[^>]+type="hidden"[^>]+name="([^"]+)"[^>]+value="([^"]*)"/i',
            $html,
            $matches,
            PREG_SET_ORDER,
        )) {
            foreach ($matches as $match) {
                $formData[$match[1]] = $match[2];
            }
        }

        // POST the form with same session
        return $client->post('/contact', ['form_params' => $formData]);
    }
}

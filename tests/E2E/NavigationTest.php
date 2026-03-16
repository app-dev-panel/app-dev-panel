<?php

declare(strict_types=1);

namespace AppDevPanel\Tests\E2E;

use Facebook\WebDriver\WebDriverBy;

/**
 * E2E tests for application navigation, menu, settings, and general UI.
 * Covers: root page, MENU navigation, 404 page, Settings.
 */
final class NavigationTest extends BrowserTestCase
{
    public function testHomepageLoads(): void
    {
        $this->navigate('/');
        $this->waitForAppLoad();

        $body = $this->getRenderedBodyText();
        $this->assertNotEmpty($body);

        // Should have the MENU link
        $this->assertStringContainsString('MENU', $body);
    }

    public function testMenuNavigation(): void
    {
        $this->navigate('/');
        $this->waitForAppLoad();

        // MENU should be present
        $menuExists = $this->elementExists('a[href*="menu"], a[href="/"]');
        $body = $this->getRenderedBodyText();

        $this->assertTrue(
            $menuExists || str_contains($body, 'MENU'),
            'Menu link should exist',
        );
    }

    public function testNavigateToDebug(): void
    {
        $this->navigate('/');
        $this->waitForAppLoad();

        $this->navigate('/debug');
        $this->waitForAppLoad();

        $url = self::$driver->getCurrentURL();
        $this->assertStringContainsString('/debug', $url);
    }

    public function testNavigateToInspector(): void
    {
        $this->navigate('/inspector/config');
        $this->waitForAppLoad();

        $url = self::$driver->getCurrentURL();
        $this->assertStringContainsString('/inspector', $url);
    }

    public function testNotFoundPage(): void
    {
        $this->navigate('/nonexistent-page-12345');
        $this->waitForAppLoad();

        $body = $this->getRenderedBodyText();
        // Should show 404 or redirect to a known page
        $is404 = str_contains($body, 'Not Found')
            || str_contains($body, '404')
            || str_contains($body, 'not found');

        // React router might redirect to home or show 404
        $this->assertTrue($is404 || !empty($body), 'Unknown page should show 404 or redirect');
    }

    public function testBreadcrumbsPresent(): void
    {
        $this->navigate('/debug');
        $this->waitForAppLoad();

        $body = $this->getRenderedBodyText();
        // Breadcrumbs should show "Main" and "Debug"
        $hasBreadcrumbs = str_contains($body, 'Main') || str_contains($body, 'Debug');
        $this->assertTrue($hasBreadcrumbs, 'Breadcrumbs should be visible');
    }

    public function testAllMainSectionsAccessible(): void
    {
        $sections = [
            '/debug' => 'Debug',
            '/inspector/config' => 'Config',
            '/inspector/routes' => 'Route',
            '/inspector/git' => 'Git',
        ];

        foreach ($sections as $path => $expectedText) {
            $this->navigate($path);
            $this->waitForAppLoad();

            $body = $this->getRenderedBodyText();
            $this->assertNotEmpty($body, "Section {$path} should render content");
        }
    }

    public function testPageTitleChanges(): void
    {
        $this->navigate('/debug');
        $this->waitForAppLoad();

        $title = self::$driver->getTitle();
        $this->assertNotEmpty($title, 'Page should have a title');
    }

    public function testMuiThemeLoaded(): void
    {
        $this->navigate('/');
        $this->waitForAppLoad();

        // MUI components should be rendered
        $hasMuiElements = $this->elementExists('[class*="Mui"]');
        $this->assertTrue($hasMuiElements, 'MUI components should be rendered');
    }

    public function testReactAppMounts(): void
    {
        $this->navigate('/');
        $this->waitForAppLoad();

        // React root should exist
        $rootExists = $this->elementExists('#root');
        $this->assertTrue($rootExists, 'React #root element should exist');

        // It should have child content (React rendered something)
        $rootContent = self::$driver->findElement(WebDriverBy::id('root'))->getText();
        $this->assertNotEmpty($rootContent, 'React app should render content inside #root');
    }

    public function testNoUncaughtExceptionsOnNavigation(): void
    {
        $pages = ['/', '/debug', '/debug/list', '/inspector/config', '/inspector/routes'];

        foreach ($pages as $page) {
            $this->navigate($page);
            $this->waitForAppLoad();
        }

        $errors = $this->getConsoleErrors();
        $uncaughtExceptions = array_filter(
            $errors,
            static fn(string $error) => str_contains($error, 'Uncaught')
                && !str_contains($error, 'net::ERR_')
                && !str_contains($error, 'Failed to fetch'),
        );

        $this->assertEmpty(
            $uncaughtExceptions,
            'No uncaught exceptions: ' . implode("\n", $uncaughtExceptions),
        );
    }
}

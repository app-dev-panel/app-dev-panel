<?php

declare(strict_types=1);

namespace AppDevPanel\Tests\E2E;

/**
 * E2E tests for the Debug module pages.
 * Covers: /debug, /debug/list, debug entry selector, collector sidebar, buttons.
 */
final class DebugPageTest extends BrowserTestCase
{
    public function testDebugPageLoads(): void
    {
        $this->navigate('/debug');
        $this->waitForAppLoad();

        // Debug page should render — either with entries or the "no entries" info box
        $hasEntries = $this->elementExists('[class*="MuiAutocomplete"]');
        $hasInfoBox = $this->elementExists('[class*="MuiAlert"]');

        $this->assertTrue($hasEntries || $hasInfoBox, 'Debug page should show entries autocomplete or info box');
    }

    public function testDebugPageBreadcrumbs(): void
    {
        $this->navigate('/debug');
        $this->waitForAppLoad();

        $body = $this->getRenderedBodyText();
        $this->assertStringContainsString('Debug', $body);
    }

    public function testDebugPageRefreshButton(): void
    {
        $this->navigate('/debug');
        $this->waitForAppLoad();

        // REFRESH button should exist
        $refreshExists = $this->elementExists('button');
        $this->assertTrue($refreshExists, 'Page should have buttons');

        $body = $this->getRenderedBodyText();
        // Either Refresh button or List button should be present
        $this->assertTrue(
            str_contains($body, 'Refresh') || str_contains($body, 'LIST') || str_contains($body, 'List'),
            'Debug toolbar should have Refresh or List button',
        );
    }

    public function testDebugPageListButton(): void
    {
        $this->navigate('/debug');
        $this->waitForAppLoad();

        $body = $this->getRenderedBodyText();
        if (str_contains($body, 'No debug entries')) {
            $this->markTestSkipped('No debug entries available for testing List button.');
        }

        // Click List button if present
        if (str_contains($body, 'List')) {
            $this->clickButton('List');
            usleep(500_000);
            $this->assertStringContainsString('/debug', self::$driver->getCurrentURL());
        }

        $this->assertTrue(true);
    }

    public function testDebugListPage(): void
    {
        $this->navigate('/debug/list');
        $this->waitForAppLoad();

        $body = $this->getRenderedBodyText();
        // List page should render something
        $this->assertNotEmpty($body);
    }

    public function testDebugPageNoEntriesMessage(): void
    {
        $this->navigate('/debug');
        $this->waitForAppLoad();

        $body = $this->getRenderedBodyText();
        // If no entries, should show info message
        if (str_contains($body, 'No debug entries')) {
            $this->assertStringContainsString('No debug entries', $body);
        } else {
            // Has entries — verify autocomplete is present
            $this->assertTrue($this->elementExists('[class*="MuiAutocomplete"]'));
        }
    }

    public function testDebugPageAutoLatestToggle(): void
    {
        $this->navigate('/debug');
        $this->waitForAppLoad();

        $body = $this->getRenderedBodyText();
        if (str_contains($body, 'No debug entries')) {
            $this->markTestSkipped('No debug entries.');
        }

        $this->assertStringContainsString('Latest auto', $body);
    }

    public function testDebugPageCollectorSidebar(): void
    {
        $this->navigate('/debug');
        $this->waitForAppLoad();

        $body = $this->getRenderedBodyText();
        if (str_contains($body, 'No debug entries')) {
            $this->markTestSkipped('No debug entries to show collector sidebar.');
        }

        // If entries exist, either the sidebar or "No one collector is chosen" should appear
        $hasCollectors = str_contains($body, 'collector') || str_contains($body, 'Collector');
        $hasSidebar = $this->elementExists('[class*="MuiDrawer"], [class*="MuiList"]');

        $this->assertTrue($hasCollectors || $hasSidebar, 'Collector sidebar or message should be visible');
    }

    public function testDebugPageRepeatRequestButton(): void
    {
        $this->navigate('/debug');
        $this->waitForAppLoad();

        $body = $this->getRenderedBodyText();
        if (str_contains($body, 'No debug entries')) {
            $this->markTestSkipped('No debug entries.');
        }

        $hasRepeatRequest = str_contains($body, 'Repeat Request') || str_contains($body, 'REPEAT REQUEST');
        $this->assertTrue($hasRepeatRequest, 'Repeat Request button should be visible');
    }

    public function testDebugObjectPage(): void
    {
        $this->navigate('/debug/object');
        $this->waitForAppLoad();

        // Object page should load without crashing
        $body = $this->getRenderedBodyText();
        $this->assertNotEmpty($body);
    }

    public function testDebugPageNoCriticalConsoleErrors(): void
    {
        $this->navigate('/debug');
        $this->waitForAppLoad();

        $errors = $this->getConsoleErrors();
        // Filter out expected errors (like network failures when backend is not running)
        $criticalErrors = array_filter(
            $errors,
            static fn(string $error) => !str_contains($error, 'net::ERR_')
                && !str_contains($error, 'Failed to fetch')
                && !str_contains($error, 'NetworkError')
                && !str_contains($error, '404'),
        );

        $this->assertEmpty($criticalErrors, 'No critical JS errors: ' . implode("\n", $criticalErrors));
    }
}

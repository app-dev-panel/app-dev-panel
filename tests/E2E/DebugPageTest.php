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

    public function testDebugListShowsSeededEntry(): void
    {
        ['path' => $path] = self::seedWebRequestEntry();

        $this->navigate('/debug/list');
        $this->waitForAppLoad();
        $this->waitForText($path);

        $body = $this->getRenderedBodyText();
        $this->assertStringNotContainsString('No debug entries', $body);
        $this->assertStringContainsString('GET', $body);
        $this->assertStringContainsString($path, $body);
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

    public function testDebugPageSelectsSeededEntry(): void
    {
        ['id' => $id, 'path' => $path] = self::seedWebRequestEntry();

        $this->navigateToEntry($id);
        $this->waitForText($path);

        // The selected entry is reflected in the URL and in the top bar entry pill
        $this->assertStringContainsString('debugEntry=' . $id, self::$driver->getCurrentURL());
        $this->assertStringContainsString($path, $this->getRenderedBodyText());
    }

    public function testDebugPageCollectorSidebar(): void
    {
        ['id' => $id, 'path' => $path] = self::seedWebRequestEntry();

        $this->navigateToEntry($id);
        $this->waitForText($path);

        // Sidebar Debug section lists the entry's collectors as links carrying `collector=`
        $this->waitForElement('a[href*="collector="]');
        $this->assertGreaterThan(0, $this->countElements('a[href*="collector="]'));
    }

    public function testDebugPageRepeatRequestButton(): void
    {
        ['id' => $id] = self::seedWebRequestEntry();

        $this->navigateToEntry($id, self::REQUEST_COLLECTOR);

        // MUI Tooltip exposes its title as aria-label on the icon button
        $this->waitForElement('button[aria-label="Repeat Request"]');
        $this->assertTrue($this->elementExists('button[aria-label="Repeat Request"]'));
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
            static fn(string $error) => (
                !str_contains($error, 'net::ERR_')
                && !str_contains($error, 'Failed to fetch')
                && !str_contains($error, 'NetworkError')
                && !str_contains($error, '404')
            ),
        );

        $this->assertEmpty($criticalErrors, 'No critical JS errors: ' . implode("\n", $criticalErrors));
    }
}

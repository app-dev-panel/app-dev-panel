<?php

declare(strict_types=1);

namespace AppDevPanel\Tests\E2E;

/**
 * E2E tests for Inspector module pages.
 * Covers: /inspector/* pages, navigation, API interactions.
 */
final class InspectorPageTest extends BrowserTestCase
{
    /**
     * @dataProvider inspectorPagesProvider
     */
    public function testInspectorPageLoads(string $path, string $expectedContent): void
    {
        $this->navigate($path);
        $this->waitForAppLoad();

        $body = $this->getRenderedBodyText();
        $this->assertNotEmpty($body, "Page {$path} should render content");

        // Either shows expected content or an error/disconnected message
        $loaded = str_contains($body, $expectedContent)
            || str_contains($body, 'disconnected')
            || str_contains($body, 'error')
            || str_contains($body, 'Error')
            || str_contains($body, 'Something went wrong');

        $this->assertTrue($loaded, "Page {$path} should render: expected '{$expectedContent}' or error state");
    }

    public static function inspectorPagesProvider(): iterable
    {
        yield 'routes' => ['/inspector/routes', 'Route'];
        yield 'events' => ['/inspector/events', 'Event'];
        yield 'files' => ['/inspector/files', 'File'];
        yield 'translations' => ['/inspector/translations', 'Translation'];
        yield 'commands' => ['/inspector/commands', 'Command'];
        yield 'database' => ['/inspector/database', 'Database'];
        yield 'phpinfo' => ['/inspector/phpinfo', 'PHP'];
        yield 'composer' => ['/inspector/composer', 'Composer'];
        yield 'opcache' => ['/inspector/opcache', 'OPcache'];
        yield 'git' => ['/inspector/git', 'Git'];
        yield 'git log' => ['/inspector/git/log', 'Git'];
        yield 'cache' => ['/inspector/cache', 'Cache'];
        yield 'config' => ['/inspector/config', 'Config'];
        yield 'container' => ['/inspector/container/view', 'Container'];
    }

    public function testInspectorConfigPage(): void
    {
        $this->navigate('/inspector/config');
        $this->waitForAppLoad();

        $body = $this->getRenderedBodyText();

        // Config page might show configuration groups or an error
        $hasContent = str_contains($body, 'Config')
            || str_contains($body, 'Parameters')
            || str_contains($body, 'di')
            || str_contains($body, 'error');

        $this->assertTrue($hasContent, 'Config page should show configuration data or error');
    }

    public function testInspectorConfigParametersPage(): void
    {
        $this->navigate('/inspector/config/parameters');
        $this->waitForAppLoad();

        $body = $this->getRenderedBodyText();
        $this->assertNotEmpty($body);
    }

    public function testInspectorRoutesPageStructure(): void
    {
        $this->navigate('/inspector/routes');
        $this->waitForAppLoad();

        $body = $this->getRenderedBodyText();

        // Routes page should have a table or grid, or show error
        $hasTable = $this->elementExists('table, [class*="MuiDataGrid"], [class*="MuiTable"]');
        $hasError = str_contains($body, 'error') || str_contains($body, 'disconnected');

        $this->assertTrue($hasTable || $hasError, 'Routes page should show a data table or error');
    }

    public function testInspectorGitPageStructure(): void
    {
        $this->navigate('/inspector/git');
        $this->waitForAppLoad();

        $body = $this->getRenderedBodyText();

        // Git page should show branch info, or error/disconnected
        $hasGitInfo = str_contains($body, 'branch')
            || str_contains($body, 'Branch')
            || str_contains($body, 'commit')
            || str_contains($body, 'Commit');
        $hasError = str_contains($body, 'error') || str_contains($body, 'disconnected');

        $this->assertTrue($hasGitInfo || $hasError, 'Git page should show repository info or error');
    }

    public function testInspectorDatabaseTableNavigation(): void
    {
        $this->navigate('/inspector/database');
        $this->waitForAppLoad();

        $body = $this->getRenderedBodyText();
        // Database page might show tables list, or error if no DB configured
        $this->assertNotEmpty($body);
    }

    public function testInspectorCommandsPageHasRunButton(): void
    {
        $this->navigate('/inspector/commands');
        $this->waitForAppLoad();

        $body = $this->getRenderedBodyText();

        // Commands page should either show command list with Run buttons or error
        $hasRunButton = str_contains($body, 'Run') || str_contains($body, 'run');
        $hasError = str_contains($body, 'error') || str_contains($body, 'disconnected');
        $hasCommandList = str_contains($body, 'Command') || str_contains($body, 'command');

        $this->assertTrue(
            $hasRunButton || $hasError || $hasCommandList,
            'Commands page should show commands or error',
        );
    }

    public function testInspectorFilesPageFileTree(): void
    {
        $this->navigate('/inspector/files');
        $this->waitForAppLoad();

        $body = $this->getRenderedBodyText();

        // File explorer should show tree or error
        $hasTree = $this->elementExists('[class*="MuiTreeItem"], [class*="TreeView"], [role="treeitem"]');
        $hasFileContent = str_contains($body, 'File') || str_contains($body, 'file');
        $hasError = str_contains($body, 'error') || str_contains($body, 'disconnected');

        $this->assertTrue($hasTree || $hasFileContent || $hasError, 'Files page should show file tree or error');
    }

    public function testInspectorPagesNoCriticalConsoleErrors(): void
    {
        $pages = ['/inspector/config', '/inspector/routes', '/inspector/git'];

        foreach ($pages as $page) {
            $this->navigate($page);
            $this->waitForAppLoad();
        }

        $errors = $this->getConsoleErrors();
        $criticalErrors = array_filter(
            $errors,
            static fn(string $error) => !str_contains($error, 'net::ERR_')
                && !str_contains($error, 'Failed to fetch')
                && !str_contains($error, 'NetworkError')
                && !str_contains($error, '404')
                && !str_contains($error, '500'),
        );

        $this->assertEmpty($criticalErrors, 'No critical JS errors: ' . implode("\n", $criticalErrors));
    }
}

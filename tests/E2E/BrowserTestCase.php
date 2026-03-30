<?php

declare(strict_types=1);

namespace AppDevPanel\Tests\E2E;

use Facebook\WebDriver\Chrome\ChromeDriver;
use Facebook\WebDriver\Chrome\ChromeDriverService;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverWait;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Base test case for E2E browser tests using headless Chromium.
 *
 * Requires:
 * - ChromeDriver binary in PATH or at CHROMEDRIVER_PATH env
 * - Chromium/Chrome binary at CHROME_BINARY env or auto-detected
 * - Frontend dev server running at FRONTEND_URL env (default: http://localhost:3000)
 *
 * Run:
 *   FRONTEND_URL=http://localhost:3000 php vendor/bin/phpunit --testsuite E2E
 */
abstract class BrowserTestCase extends TestCase
{
    protected static ?RemoteWebDriver $driver = null;
    protected static string $baseUrl = '';
    private static ?int $chromeDriverPid = null;
    private static int $chromeDriverPort = 0;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$baseUrl = rtrim(getenv('FRONTEND_URL') ?: 'http://localhost:3000', '/');
        self::$chromeDriverPort = (int) (getenv('CHROMEDRIVER_PORT') ?: 9516);

        if (!self::isChromeDriverRunning()) {
            self::startChromeDriver();
        }
        self::$driver = self::createDriver();
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$driver !== null) {
            self::$driver->quit();
            self::$driver = null;
        }

        // Only stop ChromeDriver if we started it ourselves
        if (self::$chromeDriverPid !== null) {
            self::stopChromeDriver();
        }

        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (self::$driver === null) {
            $this->markTestSkipped('WebDriver not available.');
        }
    }

    /**
     * Navigate to a frontend page path.
     */
    protected function navigate(string $path): void
    {
        self::$driver->get(self::$baseUrl . $path);
    }

    /**
     * Wait for an element to be present in the DOM.
     */
    protected function waitForElement(string $cssSelector, int $timeoutSeconds = 10): void
    {
        $wait = new WebDriverWait(self::$driver, $timeoutSeconds);
        $wait->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::cssSelector($cssSelector),
            ),
        );
    }

    /**
     * Wait for an element to be visible on the page.
     */
    protected function waitForVisible(string $cssSelector, int $timeoutSeconds = 10): void
    {
        $wait = new WebDriverWait(self::$driver, $timeoutSeconds);
        $wait->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(
                WebDriverBy::cssSelector($cssSelector),
            ),
        );
    }

    /**
     * Wait for text to appear anywhere on the page.
     */
    protected function waitForText(string $text, int $timeoutSeconds = 10): void
    {
        $wait = new WebDriverWait(self::$driver, $timeoutSeconds);
        $wait->until(
            static fn() => str_contains(self::$driver->findElement(WebDriverBy::tagName('body'))->getText(), $text),
        );
    }

    /**
     * Click a button by its visible text.
     */
    protected function clickButton(string $text): void
    {
        $button = self::$driver->findElement(
            WebDriverBy::xpath("//button[contains(., '{$text}')]"),
        );
        $button->click();
    }

    /**
     * Click a link/menu item by its visible text.
     */
    protected function clickLink(string $text): void
    {
        $link = self::$driver->findElement(
            WebDriverBy::xpath("//a[contains(., '{$text}')]"),
        );
        $link->click();
    }

    /**
     * Get the rendered DOM body text (not raw HTML source).
     * Use this instead of getPageSource() to see React-rendered content.
     */
    protected function getRenderedBodyText(): string
    {
        return self::$driver->findElement(WebDriverBy::tagName('body'))->getText();
    }

    /**
     * Get the rendered DOM body HTML (not raw HTML source).
     */
    protected function getRenderedBodyHtml(): string
    {
        return self::$driver->executeScript('return document.body.innerHTML;');
    }

    /**
     * Check if an element exists on the page.
     */
    protected function elementExists(string $cssSelector): bool
    {
        return count(self::$driver->findElements(WebDriverBy::cssSelector($cssSelector))) > 0;
    }

    /**
     * Get text content of an element.
     */
    protected function getText(string $cssSelector): string
    {
        return self::$driver->findElement(WebDriverBy::cssSelector($cssSelector))->getText();
    }

    /**
     * Get current URL path.
     */
    protected function getCurrentPath(): string
    {
        $url = self::$driver->getCurrentURL();
        $parsed = parse_url($url);

        return $parsed['path'] ?? '/';
    }

    /**
     * Take a screenshot (useful for debugging).
     */
    protected function takeScreenshot(string $name): void
    {
        $dir = __DIR__ . '/../../runtime/screenshots';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        self::$driver->takeScreenshot($dir . '/' . $name . '.png');
    }

    /**
     * Wait for React app to finish initial load.
     */
    protected function waitForAppLoad(int $timeoutSeconds = 30): void
    {
        $wait = new WebDriverWait(self::$driver, $timeoutSeconds);
        // Wait for React to actually render content inside #root
        $wait->until(
            static function () {
                $roots = self::$driver->findElements(WebDriverBy::id('root'));
                if (count($roots) === 0) {
                    return false;
                }
                $root = $roots[0];
                // Check if React has rendered child elements (not just empty div)
                $children = $root->findElements(WebDriverBy::xpath('./*'));

                return count($children) > 0;
            },
        );
        // Give React a moment to finish rendering
        usleep(500_000);
    }

    /**
     * Count elements matching a CSS selector.
     */
    protected function countElements(string $cssSelector): int
    {
        return count(self::$driver->findElements(WebDriverBy::cssSelector($cssSelector)));
    }

    /**
     * Execute JavaScript in the browser and return the result.
     */
    protected function executeJs(string $script): mixed
    {
        return self::$driver->executeScript($script);
    }

    /**
     * Check for console errors in the browser.
     */
    protected function getConsoleErrors(): array
    {
        try {
            $logs = self::$driver->manage()->getLog('browser');
        } catch (\Throwable) {
            return [];
        }
        $errors = [];
        foreach ($logs as $log) {
            if ($log['level'] === 'SEVERE') {
                $errors[] = $log['message'];
            }
        }

        return $errors;
    }

    private static function findChromeBinary(): string
    {
        $envBinary = getenv('CHROME_BINARY');
        if ($envBinary !== false && $envBinary !== '' && is_executable($envBinary)) {
            return $envBinary;
        }

        $candidates = [
            '/usr/bin/chromium',
            '/usr/bin/chromium-browser',
            '/usr/bin/google-chrome',
            '/usr/bin/google-chrome-stable',
        ];

        foreach ($candidates as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException(
            'Chrome/Chromium binary not found. Set CHROME_BINARY env variable.',
        );
    }

    private static function findChromeDriverBinary(): string
    {
        $envDriver = getenv('CHROMEDRIVER_PATH');
        if ($envDriver !== false && $envDriver !== '' && is_executable($envDriver)) {
            return $envDriver;
        }

        $path = trim(shell_exec('which chromedriver 2>/dev/null') ?? '');
        if ($path !== '' && is_executable($path)) {
            return $path;
        }

        throw new RuntimeException(
            'ChromeDriver binary not found. Set CHROMEDRIVER_PATH env variable.',
        );
    }

    private static function isChromeDriverRunning(): bool
    {
        $port = self::$chromeDriverPort;
        $ch = curl_init("http://localhost:{$port}/status");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }

    private static function startChromeDriver(): void
    {
        $chromeDriverBin = self::findChromeDriverBinary();
        $port = self::$chromeDriverPort;

        $command = sprintf(
            '%s --port=%d --silent 2>/dev/null & echo $!',
            escapeshellarg($chromeDriverBin),
            $port,
        );
        $pid = (int) trim(shell_exec($command));
        if ($pid <= 0) {
            throw new RuntimeException('Failed to start ChromeDriver.');
        }

        self::$chromeDriverPid = $pid;

        // Wait for ChromeDriver to be ready
        $maxWait = 10;
        for ($i = 0; $i < $maxWait; $i++) {
            $ch = curl_init("http://localhost:{$port}/status");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                return;
            }
            usleep(500_000);
        }

        throw new RuntimeException("ChromeDriver did not start within {$maxWait}s.");
    }

    private static function stopChromeDriver(): void
    {
        if (self::$chromeDriverPid !== null) {
            posix_kill(self::$chromeDriverPid, SIGTERM);
            self::$chromeDriverPid = null;
        }
    }

    private static function createDriver(): RemoteWebDriver
    {
        $chromeBinary = self::findChromeBinary();
        $port = self::$chromeDriverPort;

        $options = new ChromeOptions();
        $options->setBinary($chromeBinary);
        $options->addArguments([
            '--headless=new',
            '--no-sandbox',
            '--disable-gpu',
            '--disable-dev-shm-usage',
            '--window-size=1920,1080',
            '--disable-extensions',
            '--disable-background-networking',
            '--disable-software-rasterizer',
            '--mute-audio',
        ]);

        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY_W3C, $options);

        return RemoteWebDriver::create(
            "http://localhost:{$port}",
            $capabilities,
        );
    }
}

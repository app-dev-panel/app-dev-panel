<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Proxy;

use AppDevPanel\Kernel\Collector\TemplateCollector;
use Twig\Environment;
use Twig\Extension\ExtensionInterface;
use Twig\Loader\LoaderInterface;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\NodeVisitor\NodeVisitorInterface;
use Twig\RuntimeLoader\RuntimeLoaderInterface;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;
use Twig\TokenParser\TokenParserInterface;
use Twig\TokenStream;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * Decorates Twig\Environment to feed template render data to TemplateCollector.
 *
 * Intercepts render() and display() calls to capture template names, render times,
 * output content, and template parameters.
 *
 * Extends Environment so that services typed against `Twig\Environment` (e.g.
 * Symfony's BodyRenderer, TwigErrorRenderer) accept the proxy. The parent
 * constructor is invoked only to satisfy the subtype cast — every public method
 * override delegates to the decorated instance, so parent state is unused.
 */
final class TwigEnvironmentProxy extends Environment
{
    private readonly Environment $decorated;
    private readonly TemplateCollector $collector;
    private bool $ready = false;

    public function __construct(Environment $decorated, TemplateCollector $collector)
    {
        // Initialize delegation target BEFORE parent::__construct, because the
        // parent constructor invokes setLoader()/addExtension() which would
        // otherwise be forwarded to an uninitialized $this->decorated.
        // The $ready flag keeps forwarding inert until the parent finishes —
        // parent's own bootstrap operates on its inherited state and is then
        // effectively discarded (every future call delegates to $decorated).
        $this->decorated = $decorated;
        $this->collector = $collector;

        parent::__construct($decorated->getLoader());
        $this->ready = true;
    }

    public function render($name, array $context = []): string
    {
        $templateName = $name instanceof TemplateWrapper ? $name->getSourceContext()->getName() : (string) $name;

        $this->collector->beginRender($templateName);
        $start = microtime(true);

        $output = $this->decorated->render($name, $context);

        $renderTime = microtime(true) - $start;
        $this->collector->endRender($output, $context, $renderTime);

        return $output;
    }

    public function display($name, array $context = []): void
    {
        $templateName = $name instanceof TemplateWrapper ? $name->getSourceContext()->getName() : (string) $name;

        $this->collector->beginRender($templateName);
        $start = microtime(true);

        ob_start();
        $this->decorated->display($name, $context);
        $output = ob_get_clean() ?: '';

        $renderTime = microtime(true) - $start;
        $this->collector->endRender($output, $context, $renderTime);

        echo $output;
    }

    public function load($name): TemplateWrapper
    {
        return $this->decorated->load($name);
    }

    public function loadTemplate(string $cls, string $name, ?int $index = null): Template
    {
        return $this->decorated->loadTemplate($cls, $name, $index);
    }

    public function createTemplate(string $template, ?string $name = null): TemplateWrapper
    {
        return $this->decorated->createTemplate($template, $name);
    }

    public function resolveTemplate($names): TemplateWrapper
    {
        return $this->decorated->resolveTemplate($names);
    }

    public function isTemplateFresh(string $name, int $time): bool
    {
        return $this->decorated->isTemplateFresh($name, $time);
    }

    public function getTemplateClass(string $name, ?int $index = null): string
    {
        return $this->decorated->getTemplateClass($name, $index);
    }

    public function useYield(): bool
    {
        return $this->decorated->useYield();
    }

    public function enableDebug(): void
    {
        $this->decorated->enableDebug();
    }

    public function disableDebug(): void
    {
        $this->decorated->disableDebug();
    }

    public function isDebug(): bool
    {
        return $this->decorated->isDebug();
    }

    public function enableAutoReload(): void
    {
        $this->decorated->enableAutoReload();
    }

    public function disableAutoReload(): void
    {
        $this->decorated->disableAutoReload();
    }

    public function isAutoReload(): bool
    {
        return $this->decorated->isAutoReload();
    }

    public function enableStrictVariables(): void
    {
        $this->decorated->enableStrictVariables();
    }

    public function disableStrictVariables(): void
    {
        $this->decorated->disableStrictVariables();
    }

    public function isStrictVariables(): bool
    {
        return $this->decorated->isStrictVariables();
    }

    public function removeCache(string $name): void
    {
        $this->decorated->removeCache($name);
    }

    public function getCache($original = true)
    {
        return $this->decorated->getCache($original);
    }

    public function setCache($cache): void
    {
        if (!$this->ready) {
            parent::setCache($cache);
            return;
        }
        $this->decorated->setCache($cache);
    }

    public function tokenize(Source $source): TokenStream
    {
        return $this->decorated->tokenize($source);
    }

    public function parse(TokenStream $stream): ModuleNode
    {
        return $this->decorated->parse($stream);
    }

    public function compile(Node $node): string
    {
        return $this->decorated->compile($node);
    }

    public function compileSource(Source $source): string
    {
        return $this->decorated->compileSource($source);
    }

    public function setLoader(LoaderInterface $loader): void
    {
        if (!$this->ready) {
            parent::setLoader($loader);
            return;
        }
        $this->decorated->setLoader($loader);
    }

    public function getLoader(): LoaderInterface
    {
        return $this->decorated->getLoader();
    }

    public function setCharset(string $charset): void
    {
        if (!$this->ready) {
            parent::setCharset($charset);
            return;
        }
        $this->decorated->setCharset($charset);
    }

    public function getCharset(): string
    {
        return $this->decorated->getCharset();
    }

    public function hasExtension(string $class): bool
    {
        return $this->decorated->hasExtension($class);
    }

    public function addRuntimeLoader(RuntimeLoaderInterface $loader): void
    {
        $this->decorated->addRuntimeLoader($loader);
    }

    public function getExtension(string $class): ExtensionInterface
    {
        return $this->decorated->getExtension($class);
    }

    public function getRuntime(string $class)
    {
        return $this->decorated->getRuntime($class);
    }

    public function addExtension(ExtensionInterface $extension): void
    {
        if (!$this->ready) {
            parent::addExtension($extension);
            return;
        }
        $this->decorated->addExtension($extension);
    }

    public function setExtensions(array $extensions): void
    {
        $this->decorated->setExtensions($extensions);
    }

    public function getExtensions(): array
    {
        return $this->decorated->getExtensions();
    }

    public function addTokenParser(TokenParserInterface $parser): void
    {
        $this->decorated->addTokenParser($parser);
    }

    public function getTokenParsers(): array
    {
        return $this->decorated->getTokenParsers();
    }

    public function getTokenParser(string $name): ?TokenParserInterface
    {
        return $this->decorated->getTokenParser($name);
    }

    public function registerUndefinedTokenParserCallback(callable $callable): void
    {
        $this->decorated->registerUndefinedTokenParserCallback($callable);
    }

    public function addNodeVisitor(NodeVisitorInterface $visitor): void
    {
        $this->decorated->addNodeVisitor($visitor);
    }

    public function getNodeVisitors(): array
    {
        return $this->decorated->getNodeVisitors();
    }

    public function addFilter(TwigFilter $filter): void
    {
        $this->decorated->addFilter($filter);
    }

    public function getFilter(string $name): ?TwigFilter
    {
        return $this->decorated->getFilter($name);
    }

    public function registerUndefinedFilterCallback(callable $callable): void
    {
        $this->decorated->registerUndefinedFilterCallback($callable);
    }

    public function getFilters(): array
    {
        return $this->decorated->getFilters();
    }

    public function addTest(TwigTest $test): void
    {
        $this->decorated->addTest($test);
    }

    public function getTests(): array
    {
        return $this->decorated->getTests();
    }

    public function getTest(string $name): ?TwigTest
    {
        return $this->decorated->getTest($name);
    }

    public function registerUndefinedTestCallback(callable $callable): void
    {
        $this->decorated->registerUndefinedTestCallback($callable);
    }

    public function addFunction(TwigFunction $function): void
    {
        $this->decorated->addFunction($function);
    }

    public function getFunction(string $name): ?TwigFunction
    {
        return $this->decorated->getFunction($name);
    }

    public function registerUndefinedFunctionCallback(callable $callable): void
    {
        $this->decorated->registerUndefinedFunctionCallback($callable);
    }

    public function getFunctions(): array
    {
        return $this->decorated->getFunctions();
    }

    public function addGlobal(string $name, $value): void
    {
        $this->decorated->addGlobal($name, $value);
    }

    public function getGlobals(): array
    {
        return $this->decorated->getGlobals();
    }

    public function resetGlobals(): void
    {
        $this->decorated->resetGlobals();
    }

    public function mergeGlobals(array $context): array
    {
        return $this->decorated->mergeGlobals($context);
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->decorated->$name(...$arguments);
    }
}

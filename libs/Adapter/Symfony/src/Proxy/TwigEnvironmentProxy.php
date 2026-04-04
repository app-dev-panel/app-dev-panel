<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Proxy;

use AppDevPanel\Kernel\Collector\TemplateCollector;
use Twig\Environment;
use Twig\Markup;
use Twig\Template;
use Twig\TemplateWrapper;

/**
 * Decorates Twig\Environment to feed template render data to TemplateCollector.
 *
 * Intercepts render() and display() calls to capture template names, render times,
 * output content, and template parameters.
 *
 * Uses Symfony's service decoration: replaces the 'twig' service while delegating
 * to the original Environment for actual rendering.
 *
 * Cannot extend Environment directly (complex constructor), so this uses
 * __call() delegation for unmapped methods. Symfony's DI decorator pattern ensures
 * type-safe injection because the container resolves by service ID, not class name.
 */
final class TwigEnvironmentProxy
{
    private readonly Environment $decorated;
    private readonly TemplateCollector $collector;

    public function __construct(Environment $decorated, TemplateCollector $collector)
    {
        $this->decorated = $decorated;
        $this->collector = $collector;
    }

    public function render(string|TemplateWrapper $name, array $context = []): string
    {
        $templateName = $name instanceof TemplateWrapper ? $name->getSourceContext()->getName() : $name;

        $this->collector->beginRender($templateName);
        $start = microtime(true);

        $output = $this->decorated->render($name, $context);

        $renderTime = microtime(true) - $start;
        $this->collector->endRender($output, $context, $renderTime);

        return $output;
    }

    public function display(string|TemplateWrapper $name, array $context = []): void
    {
        $templateName = $name instanceof TemplateWrapper ? $name->getSourceContext()->getName() : $name;

        $this->collector->beginRender($templateName);
        $start = microtime(true);

        ob_start();
        $this->decorated->display($name, $context);
        $output = ob_get_clean() ?: '';

        $renderTime = microtime(true) - $start;
        $this->collector->endRender($output, $context, $renderTime);

        echo $output;
    }

    public function load(string|TemplateWrapper $name): TemplateWrapper
    {
        return $this->decorated->load($name);
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->decorated->$name(...$arguments);
    }

    public function __get(string $name): mixed
    {
        return $this->decorated->$name;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->decorated->$name = $value;
    }
}

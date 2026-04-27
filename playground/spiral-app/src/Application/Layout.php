<?php

declare(strict_types=1);

namespace App\Application;

/**
 * Shared HTML layout for the demo pages — mirrors the structure/CSS of the Yii 3 playground
 * so visitors can compare the two adapters side by side. Keeps styles inline so the playground
 * has zero asset pipeline.
 */
final class Layout
{
    /**
     * @param list<array{href: string, label: string, external?: bool}> $navItems
     */
    public function __construct(
        private readonly string $brandBadge = 'Spiral',
        private readonly array $navItems = [
            ['href' => '/', 'label' => 'Home'],
            ['href' => '/users', 'label' => 'Users'],
            ['href' => '/contact', 'label' => 'Contact'],
            ['href' => '/api-playground', 'label' => 'API Playground'],
            ['href' => '/error', 'label' => 'Error Demo'],
            ['href' => '/log-demo', 'label' => 'Log Demo'],
            ['href' => '/var-dumper', 'label' => 'Var Dumper'],
            ['href' => '/api/openapi.json', 'label' => 'OpenAPI'],
            ['href' => '/debug', 'label' => 'Debug Panel', 'external' => true],
        ],
    ) {}

    public function render(string $title, string $content, string $currentPath = '/'): string
    {
        $titleAttr = htmlspecialchars($title === '' ? 'ADP Spiral Playground' : $title . ' — ADP Spiral Playground');
        $brand = htmlspecialchars($this->brandBadge);
        $nav = $this->renderNav($currentPath);

        return <<<HTML
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title>{$titleAttr}</title>
                {$this->styles()}
            </head>
            <body>
            <div class="header">
                <a href="/" class="header-brand">ADP Playground <span class="badge">{$brand}</span></a>
                <nav>{$nav}</nav>
            </div>

            <div class="main">{$content}</div>

            <div class="footer">
                ADP Playground — Spiral | Powered by
                <a href="https://github.com/app-dev-panel">Application Development Panel</a>
            </div>
            </body>
            </html>
            HTML;
    }

    private function renderNav(string $currentPath): string
    {
        $html = '';
        foreach ($this->navItems as $item) {
            $active = $item['href'] === $currentPath ? ' class="active"' : '';
            $extra = !empty($item['external']) ? ' target="_blank" rel="noopener"' : '';
            $href = htmlspecialchars($item['href']);
            $label = htmlspecialchars($item['label']);
            $html .= "<a href=\"{$href}\"{$active}{$extra}>{$label}</a>";
        }
        return $html;
    }

    private function styles(): string
    {
        return <<<CSS
            <style>
            :root {
                --color-bg: #f5f5f7; --color-surface: #fff; --color-header: #1a1a2e;
                --color-header-text: #e0e0e0; --color-primary: #4361ee; --color-primary-hover: #3a56d4;
                --color-text: #1a1a2e; --color-text-secondary: #555; --color-border: #e0e0e2;
                --color-success: #16a34a; --color-error: #dc2626; --color-code-bg: #1e1e2e;
                --color-code-text: #cdd6f4; --color-badge-bg: #e8eaff; --color-badge-text: #4361ee;
                --color-warning: #d97706;
                --radius: 8px; --radius-lg: 12px;
                --shadow: 0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.06);
                --shadow-lg: 0 4px 12px rgba(0,0,0,0.1);
                --font: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                --font-mono: 'SF Mono', 'Cascadia Code', 'JetBrains Mono', Consolas, monospace;
                --max-width: 960px;
            }
            *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
            body { font-family: var(--font); background: var(--color-bg); color: var(--color-text); line-height: 1.6; min-height: 100vh; display: flex; flex-direction: column; }
            .header { background: var(--color-header); color: var(--color-header-text); padding: 0 24px; display: flex; align-items: center; justify-content: space-between; height: 56px; position: sticky; top: 0; z-index: 100; }
            .header-brand { display: flex; align-items: center; gap: 10px; text-decoration: none; color: #fff; font-weight: 600; font-size: 15px; }
            .header-brand .badge { background: var(--color-primary); color: #fff; font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 4px; letter-spacing: 0.5px; text-transform: uppercase; }
            .header nav { display: flex; gap: 4px; }
            .header nav a { color: var(--color-header-text); text-decoration: none; font-size: 13px; padding: 6px 12px; border-radius: 6px; transition: background 0.15s, color 0.15s; }
            .header nav a:hover, .header nav a.active { background: rgba(255,255,255,0.1); color: #fff; }
            .main { flex: 1; max-width: var(--max-width); width: 100%; margin: 0 auto; padding: 32px 24px; }
            .card { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 24px; box-shadow: var(--shadow); }
            .card + .card { margin-top: 16px; }
            .card-title { font-size: 16px; font-weight: 600; margin-bottom: 16px; }
            .page-header { margin-bottom: 24px; }
            .page-header h1 { font-size: 24px; font-weight: 700; margin-bottom: 4px; }
            .page-header p { color: var(--color-text-secondary); font-size: 14px; }
            table { width: 100%; border-collapse: collapse; font-size: 14px; }
            thead th { text-align: left; padding: 10px 12px; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--color-text-secondary); border-bottom: 2px solid var(--color-border); }
            tbody td { padding: 10px 12px; border-bottom: 1px solid var(--color-border); }
            tbody tr:last-child td { border-bottom: none; }
            tbody tr:hover { background: #f8f8fa; }
            .form-group { margin-bottom: 16px; }
            .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
            .form-control { width: 100%; padding: 9px 12px; font-size: 14px; font-family: var(--font); border: 1px solid var(--color-border); border-radius: var(--radius); background: var(--color-surface); color: var(--color-text); }
            .form-control:focus { outline: none; border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(67,97,238,0.15); }
            .btn { display: inline-block; padding: 9px 18px; font-size: 14px; font-weight: 600; border-radius: var(--radius); cursor: pointer; border: none; text-decoration: none; transition: background 0.15s; }
            .btn-primary { background: var(--color-primary); color: #fff; }
            .btn-primary:hover { background: var(--color-primary-hover); }
            .code-block { background: var(--color-code-bg); color: var(--color-code-text); border-radius: var(--radius); padding: 16px; font-family: var(--font-mono); font-size: 13px; line-height: 1.5; overflow-x: auto; white-space: pre-wrap; word-break: break-word; }
            .grid { display: grid; gap: 16px; }
            .grid-2 { grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
            .feature-card { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 20px; box-shadow: var(--shadow); transition: box-shadow 0.15s, transform 0.15s; text-decoration: none; color: inherit; display: block; }
            .feature-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-2px); }
            .feature-card h3 { font-size: 15px; font-weight: 600; margin-bottom: 6px; }
            .feature-card p { font-size: 13px; color: var(--color-text-secondary); }
            .alert { padding: 12px 16px; border-radius: var(--radius); font-size: 14px; margin-bottom: 16px; }
            .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
            .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
            .alert-info { background: #dbeafe; color: #1d4ed8; border: 1px solid #bfdbfe; }
            .footer { text-align: center; padding: 20px 24px; font-size: 12px; color: var(--color-text-secondary); border-top: 1px solid var(--color-border); margin-top: auto; }
            .footer a { color: var(--color-primary); text-decoration: none; }
            .footer a:hover { text-decoration: underline; }
            </style>
            CSS;
    }
}

<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Debug\Slot;

use AppDevPanel\Api\Debug\Slot\Slot;
use PHPUnit\Framework\TestCase;

final class SlotTest extends TestCase
{
    public function testJsonWrapsPayloadInScriptTag(): void
    {
        $html = Slot::json('json', ['foo' => 'bar', 'list' => [1, 2, 3]]);

        $this->assertStringContainsString('data-adp-slot="json"', $html);
        $this->assertStringContainsString('<script type="application/json" data-adp-payload>', $html);
        $this->assertStringContainsString('"foo":"bar"', $html);
        $this->assertStringContainsString('[1,2,3]', $html);
        $this->assertStringStartsWith('<div ', $html);
        $this->assertStringEndsWith('</div>', $html);
    }

    public function testJsonNeutralisesClosingScriptInPayload(): void
    {
        $html = Slot::json('json', ['x' => '</script><script>alert(1)</script>']);

        // The literal closing `</` sequence inside a <script> body is escaped to <\/.
        $this->assertStringNotContainsString('</script><script>', $html);
        $this->assertStringContainsString('<\/script>', $html);
        // The wrapper's own closing tag must still be intact.
        $this->assertStringEndsWith('</script></div>', $html);
    }

    public function testJsonAcceptsCustomTag(): void
    {
        $html = Slot::json('foo', null, 'section');
        $this->assertStringStartsWith('<section ', $html);
        $this->assertStringEndsWith('</section>', $html);
    }

    public function testAttrsEmitsDataAttributesAndLabel(): void
    {
        $html = Slot::attrs('file-link', ['path' => '/app/Foo.php', 'line' => 42], '/app/Foo.php:42', 'a');

        $this->assertStringContainsString('<a ', $html);
        $this->assertStringContainsString('data-adp-slot="file-link"', $html);
        $this->assertStringContainsString('data-path="/app/Foo.php"', $html);
        $this->assertStringContainsString('data-line="42"', $html);
        $this->assertStringContainsString('>/app/Foo.php:42</a>', $html);
    }

    public function testAttrsEscapesAttributesAndLabel(): void
    {
        $html = Slot::attrs('class-name', ['fqcn' => 'App\\"Evil"\\Class', 'method' => '<script>'], '<b>label</b>');

        $this->assertStringNotContainsString('"Evil"', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<b>label</b>', $html);
        $this->assertStringContainsString('data-fqcn="App\\&quot;Evil&quot;\\Class"', $html);
        $this->assertStringContainsString('&lt;b&gt;label&lt;/b&gt;', $html);
    }

    public function testAttrsSkipsNullAndEmptyAttrs(): void
    {
        $html = Slot::attrs('x', ['a' => 'one', 'b' => null, 'c' => ''], 'lbl');
        $this->assertStringContainsString('data-a="one"', $html);
        $this->assertStringNotContainsString('data-b=', $html);
        $this->assertStringNotContainsString('data-c=', $html);
    }

    public function testTextEscapesContent(): void
    {
        $html = Slot::text('sql', "SELECT * FROM users WHERE name = 'O\\'Brien' AND age > 5");
        $this->assertStringStartsWith('<pre data-adp-slot="sql">', $html);
        $this->assertStringEndsWith('</pre>', $html);
        $this->assertStringContainsString('&#039;O\\&#039;Brien&#039;', $html);
        $this->assertStringContainsString('age &gt; 5', $html);
    }

    public function testInvalidTagIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Slot::text('x', 'y', 'pre><script>alert(1)</script');
    }

    public function testSlotNameItselfIsEscaped(): void
    {
        $html = Slot::text('"><img onerror=x>', 'hi');
        $this->assertStringNotContainsString('"><img', $html);
        $this->assertStringContainsString('data-adp-slot="&quot;&gt;&lt;img onerror=x&gt;"', $html);
    }
}

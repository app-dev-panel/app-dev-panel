/**
 * Render a PHP class as a fully-qualified name with a leading backslash,
 * matching how you'd write `use \App\Foo;` or `\RuntimeException::class`
 * in source code.
 *
 * PHP's own `::class` produces an FQCN without the leading slash
 * (`App\Foo`, `RuntimeException`), so the UI layer adds it back so that
 * global classes are visibly distinguishable from namespaced ones.
 */
export const formatFqcn = (raw: string): string => {
    if (!raw) return '';
    return raw.startsWith('\\') ? raw : '\\' + raw;
};

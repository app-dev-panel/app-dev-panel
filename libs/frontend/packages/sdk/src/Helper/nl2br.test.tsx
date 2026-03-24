import {render, screen} from '@testing-library/react';
import {describe, expect, it} from 'vitest';
import {nl2br} from './nl2br';

describe('nl2br', () => {
    it('returns a single span for text without newlines', () => {
        const result = nl2br('hello');
        render(<div data-testid="container">{result}</div>);
        expect(screen.getByTestId('container').querySelectorAll('span')).toHaveLength(1);
        expect(screen.getByText('hello')).toBeInTheDocument();
    });

    it('splits text on newlines and wraps each line in a span with br', () => {
        const result = nl2br('line1\nline2\nline3');
        render(<div data-testid="container">{result}</div>);
        const spans = screen.getByTestId('container').querySelectorAll('span');
        expect(spans).toHaveLength(3);
        expect(spans[0]).toHaveTextContent('line1');
        expect(spans[1]).toHaveTextContent('line2');
        expect(spans[2]).toHaveTextContent('line3');
    });

    it('each span contains a br element', () => {
        const result = nl2br('a\nb');
        render(<div data-testid="container">{result}</div>);
        const brs = screen.getByTestId('container').querySelectorAll('br');
        expect(brs).toHaveLength(2);
    });

    it('handles empty string', () => {
        const result = nl2br('');
        render(<div data-testid="container">{result}</div>);
        const spans = screen.getByTestId('container').querySelectorAll('span');
        expect(spans).toHaveLength(1);
        expect(spans[0]).toHaveTextContent('');
    });

    it('handles string with only newlines', () => {
        const result = nl2br('\n\n');
        render(<div data-testid="container">{result}</div>);
        const spans = screen.getByTestId('container').querySelectorAll('span');
        expect(spans).toHaveLength(3);
    });

    it('preserves special characters', () => {
        const result = nl2br('foo & bar\n<baz>');
        render(<div data-testid="container">{result}</div>);
        expect(screen.getByText('foo & bar')).toBeInTheDocument();
        expect(screen.getByText('<baz>')).toBeInTheDocument();
    });
});

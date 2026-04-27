import {describe, expect, it} from 'vitest';
import {parseSlotEntry, readSlotAttrs, readSlotPayload, ssrSlots} from './SsrPanel.slots';

const mount = (html: string): Element => {
    const wrapper = document.createElement('div');
    wrapper.innerHTML = html.trim();
    return wrapper.firstElementChild!;
};

describe('SsrPanel.slots — parsers', () => {
    it('readSlotPayload parses a JSON <script> payload', () => {
        const el = mount(
            '<div data-adp-slot="json">' +
                '<script type="application/json" data-adp-payload>{"a":1,"b":[2,3]}</script>' +
                '</div>',
        );
        expect(readSlotPayload(el)).toEqual({a: 1, b: [2, 3]});
    });

    it('readSlotPayload returns undefined when there is no payload script', () => {
        const el = mount('<span data-adp-slot="file-link" data-path="/x.php">/x.php</span>');
        expect(readSlotPayload(el)).toBeUndefined();
    });

    it('readSlotPayload swallows malformed JSON and returns undefined', () => {
        const el = mount(
            '<div data-adp-slot="json"><script type="application/json" data-adp-payload>{not-json}</script></div>',
        );
        expect(readSlotPayload(el)).toBeUndefined();
    });

    it('readSlotAttrs returns every data-* attribute except data-adp-slot', () => {
        const el = mount(
            '<a data-adp-slot="file-link" data-path="/app/Foo.php" data-line="42" data-extra="x" id="ignored">x</a>',
        );
        expect(readSlotAttrs(el)).toEqual({path: '/app/Foo.php', line: '42', extra: 'x'});
    });

    it('parseSlotEntry collects payload + attrs + label, suppresses label for json slots', () => {
        const jsonEl = mount(
            '<div data-adp-slot="json">' +
                '<script type="application/json" data-adp-payload>{"x":1}</script>' +
                '</div>',
        );
        expect(parseSlotEntry(jsonEl)).toEqual({payload: {x: 1}, attrs: {}, label: ''});

        const linkEl = mount('<a data-adp-slot="file-link" data-path="/app/Foo.php" data-line="3">/app/Foo.php:3</a>');
        expect(parseSlotEntry(linkEl)).toEqual({
            payload: undefined,
            attrs: {path: '/app/Foo.php', line: '3'},
            label: '/app/Foo.php:3',
        });
    });

    it('only known slot names live in the registry', () => {
        expect(Object.keys(ssrSlots).sort()).toEqual(['class-name', 'file-link', 'json']);
    });
});

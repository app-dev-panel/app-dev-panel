const RU_EN: Record<string, string> = {
    й: 'q',
    ц: 'w',
    у: 'e',
    к: 'r',
    е: 't',
    н: 'y',
    г: 'u',
    ш: 'i',
    щ: 'o',
    з: 'p',
    х: '[',
    ъ: ']',
    ф: 'a',
    ы: 's',
    в: 'd',
    а: 'f',
    п: 'g',
    р: 'h',
    о: 'j',
    л: 'k',
    д: 'l',
    ж: ';',
    э: "'",
    я: 'z',
    ч: 'x',
    с: 'c',
    м: 'v',
    и: 'b',
    т: 'n',
    ь: 'm',
    ю: '.',
    Й: 'Q',
    Ц: 'W',
    У: 'E',
    К: 'R',
    Е: 'T',
    Н: 'Y',
    Г: 'U',
    Ш: 'I',
    Щ: 'O',
    З: 'P',
    Х: '{',
    Ъ: '}',
    Ф: 'A',
    Ы: 'S',
    В: 'D',
    А: 'F',
    П: 'G',
    Р: 'H',
    О: 'J',
    Л: 'K',
    Д: 'L',
    Ж: ':',
    Э: '"',
    Я: 'Z',
    Ч: 'X',
    С: 'C',
    М: 'V',
    И: 'B',
    Т: 'N',
    Ь: 'M',
    Ю: '>',
};

const EN_RU: Record<string, string> = Object.fromEntries(Object.entries(RU_EN).map(([ru, en]) => [en, ru]));

/** Transliterates a string from one keyboard layout to the other (ЙЦУКЕН ↔ QWERTY). */
export function translit(str: string): string {
    return str
        .split('')
        .map((ch) => RU_EN[ch] ?? EN_RU[ch] ?? ch)
        .join('');
}

/**
 * Returns an array of search variants: the original query and its layout-transliterated version.
 * Deduplicates when the query contains no transliterable characters.
 */
export function searchVariants(query: string): string[] {
    const transliterated = translit(query);
    if (transliterated === query) return [query];
    return [query, transliterated];
}

export type FuzzyMatch = {score: number; indices: number[]};

/**
 * Fuzzy-match `query` against `text`.
 * Returns null if no match, or {score, indices} where indices are
 * the character positions in `text` that matched.
 * Score: lower is better. Consecutive matches and early matches score best.
 */
export function fuzzyMatch(text: string, query: string): FuzzyMatch | null {
    const textLower = text.toLowerCase();
    const queryLower = query.toLowerCase();

    if (queryLower.length === 0) return {score: 0, indices: []};

    const indices: number[] = [];
    let qi = 0;

    for (let ti = 0; ti < textLower.length && qi < queryLower.length; ti++) {
        if (textLower[ti] === queryLower[qi]) {
            indices.push(ti);
            qi++;
        }
    }

    if (qi < queryLower.length) return null; // not all query chars matched

    // Score: penalize gaps between matched characters and late starts
    let score = indices[0]; // penalize late start
    for (let i = 1; i < indices.length; i++) {
        const gap = indices[i] - indices[i - 1] - 1;
        score += gap * 2; // penalize gaps
    }

    // Bonus for exact substring match
    if (textLower.includes(queryLower)) {
        score -= queryLower.length * 3;
    }

    return {score, indices};
}

export function matchInputType(rules: string[]) {
    let possibleType = 'text';
    for (const rule of rules) {
        if (rule === 'each') {
            possibleType = 'select';
            break;
        }
        if (rule === 'number') {
            possibleType = 'number';
            break;
        }
    }
    return possibleType;
}

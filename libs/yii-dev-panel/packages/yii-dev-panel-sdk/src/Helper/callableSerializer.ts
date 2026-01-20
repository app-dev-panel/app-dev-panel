export const serializeCallable = (raw: any): string => {
    switch (true) {
        case Array.isArray(raw) && raw.length === 2:
            return raw.join('::') + '()';
        case typeof raw === 'string':
            return raw;
        default:
            return JSON.stringify(raw);
    }
};

export const parseObjectId = (string: string) => {
    return Number(string.substring(string.indexOf('#', -1) + 1));
};
export const toObjectReference = (string: string) => {
    return string.replace('object@', '');
};
export const toObjectString = (classString: string, id: number) => {
    return 'object@' + classString + '#' + id;
};

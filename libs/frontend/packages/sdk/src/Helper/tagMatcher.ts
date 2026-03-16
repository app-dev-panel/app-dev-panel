export const isTagString = (value: string): boolean => {
    return value.match(/^tag@[a-z0-9.]+$/i)?.length === 1;
};

export const joinUrl = (base: string, path: string): string => base.replace(/\/$/, '') + path;

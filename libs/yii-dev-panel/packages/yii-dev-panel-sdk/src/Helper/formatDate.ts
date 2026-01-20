import {format, fromUnixTime} from 'date-fns';

export function formatDate(unixTimeStamp: number) {
    return format(fromUnixTime(unixTimeStamp), 'do MMM HH:mm:ss');
}

export function formatMicrotime(unixTimeStamp: number) {
    if (!unixTimeStamp) {
        return '0.000000';
    }
    return formatWithMicrotime(unixTimeStamp, 'HH:mm:ss');
}
export function formatWithMicrotime(unixTimeStamp: number, dateFormat: string) {
    const float = String(unixTimeStamp).split('.');
    return format(fromUnixTime(+float[0]), dateFormat) + (float.length === 2 ? '.' + float[1].padEnd(6, '0') : '');
}

export function formatMillisecondsAsDuration(milliseconds: number) {
    return `${(milliseconds * 1000).toFixed(3)} ms`;
}

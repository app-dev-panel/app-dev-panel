export type LogLevel = 'emergency' | 'alert' | 'critical' | 'error' | 'warning' | 'notice' | 'info' | 'debug';

export const LOG_LEVELS: LogLevel[] = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

export type LogLevelGroup = 'errors' | 'warnings' | 'info';

export const LOG_LEVEL_GROUPS: Record<LogLevelGroup, LogLevel[]> = {
    errors: ['emergency', 'alert', 'critical', 'error'],
    warnings: ['warning', 'notice'],
    info: ['info', 'debug'],
};

export const LOG_LEVEL_GROUP_ORDER: LogLevelGroup[] = ['info', 'warnings', 'errors'];

export function isLogLevel(value: string): value is LogLevel {
    return (LOG_LEVELS as string[]).includes(value);
}

export function sumLevels(byLevel: Partial<Record<LogLevel, number>> | undefined, levels: LogLevel[]): number {
    if (!byLevel) return 0;
    let total = 0;
    for (const level of levels) {
        total += byLevel[level] ?? 0;
    }
    return total;
}

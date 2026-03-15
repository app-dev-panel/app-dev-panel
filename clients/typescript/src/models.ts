export type RequestInfo = {
    method: string;
    uri: string;
    headers?: Record<string, string>;
    statusCode?: number;
    duration?: number;
};

export type DebugContext = {
    type?: 'web' | 'command' | 'generic';
    language?: string;
    service?: string;
    request?: RequestInfo;
    command?: string;
    environment?: Record<string, string>;
};

export type LogEntry = {
    level: 'emergency' | 'alert' | 'critical' | 'error' | 'warning' | 'notice' | 'info' | 'debug';
    message: string;
    context?: Record<string, unknown>;
    line?: string;
    service?: string;
};

export type DebugEntry = {
    debugId?: string;
    context?: DebugContext;
    collectors: Record<string, Record<string, unknown>[]>;
    summary?: Record<string, unknown>;
};

export type IngestResponse = {
    id: string;
    success: boolean;
};

export type BatchIngestResponse = {
    ids: string[];
    count: number;
};

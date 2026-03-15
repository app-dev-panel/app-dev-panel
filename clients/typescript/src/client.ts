import type { BatchIngestResponse, DebugEntry, IngestResponse, LogEntry } from './models';

/**
 * Client for sending debug data to App Dev Panel.
 *
 * @example
 * ```ts
 * const client = new ADPClient('http://localhost:8080');
 *
 * // Send a full debug entry
 * await client.ingest({
 *     collectors: {
 *         logs: [{ time: Date.now() / 1000, level: 'info', message: 'Hello' }],
 *         http_client: [{ method: 'GET', uri: '/api', totalTime: 0.5 }],
 *     },
 *     context: { service: 'my-app', language: 'typescript' },
 * });
 *
 * // Send a single log (shorthand)
 * await client.log('error', 'Something went wrong', { line: 'app.ts:42' });
 * ```
 */
export class ADPClient {
    private baseUrl: string;

    constructor(baseUrl: string = 'http://localhost:8080') {
        this.baseUrl = baseUrl.replace(/\/+$/, '');
    }

    async ingest(entry: DebugEntry): Promise<IngestResponse> {
        return this.post('/debug/api/ingest', entry);
    }

    async ingestBatch(entries: DebugEntry[]): Promise<BatchIngestResponse> {
        return this.post('/debug/api/ingest/batch', { entries });
    }

    async log(
        level: LogEntry['level'],
        message: string,
        options?: { context?: Record<string, unknown>; line?: string; service?: string },
    ): Promise<IngestResponse> {
        const body: LogEntry = { level, message, ...options };
        return this.post('/debug/api/ingest/log', body);
    }

    async getOpenApiSpec(): Promise<Record<string, unknown>> {
        const resp = await fetch(`${this.baseUrl}/debug/api/openapi.json`);
        if (!resp.ok) throw new Error(`HTTP ${resp.status}: ${await resp.text()}`);
        return resp.json();
    }

    private async post<T>(path: string, body: unknown): Promise<T> {
        const resp = await fetch(`${this.baseUrl}${path}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        });
        if (!resp.ok) throw new Error(`HTTP ${resp.status}: ${await resp.text()}`);
        return resp.json();
    }
}

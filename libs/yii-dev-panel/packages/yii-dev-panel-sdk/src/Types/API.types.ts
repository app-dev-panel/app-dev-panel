/**
 * Shared API types for ADP frontend.
 *
 * These types mirror the backend API response structures.
 * Keep in sync with backend controllers when API changes.
 */

/** Standard API response wrapper used by all ADP endpoints. */
export type ApiResponse<T = unknown> = {
    id: string | null;
    data: T;
    error: string | null;
    success: boolean;
    status: number;
};

/** Service descriptor from the Service Registry API. */
export type ServiceDescriptor = {
    service: string;
    language: string;
    inspectorUrl: string | null;
    capabilities: string[];
    registeredAt: number;
    lastSeenAt: number;
    online: boolean;
};

/** Inspector file entry from the File Explorer API. */
export type InspectorFile = {
    path: string;
    type: 'file' | 'directory';
    size?: number;
};

/** Command definition from the Command API. */
export type CommandType = {
    name: string;
    title: string;
    group: string;
    description: string;
};

/** Command execution response. */
export type CommandResponseType = {
    status: string;
    result: string;
    errors: string[];
};

/** Git summary from the Git API. */
export type GitSummary = {
    currentBranch: string;
    branches: string[];
    lastCommit: {sha: string; message: string; author: string; date: string};
    remotes: Record<string, string>;
    status: string[];
};

/** Git log entry. */
export type GitCommit = {
    sha: string;
    message: string;
    author: string;
    date: string;
};

/** Route definition from the Routes API. */
export type RouteDefinition = {
    name: string;
    pattern: string;
    host: string;
    methods: string[];
    defaults: Record<string, string>;
    middlewares: string[];
};

/** Event listener type from the Events API. */
export type EventListenerType = {
    event: string;
    listeners: string[];
};

/** Event listeners grouped by context. */
export type EventListeners = {
    common: EventListenerType[];
    web: EventListenerType[];
    console: EventListenerType[];
};

/** OPcache status from the OPcache API. */
export type OpcacheStatus = {
    enabled: boolean;
    cacheFull: boolean;
    restartPending: boolean;
    restartInProgress: boolean;
    memoryUsage: {usedMemory: number; freeMemory: number; wastedMemory: number; currentWastedPercentage: number};
    statistics: {numCachedScripts: number; numCachedKeys: number; maxCachedKeys: number; hits: number; misses: number};
    jit?: {enabled: boolean; on: boolean; kind: number; bufferSize: number; bufferFree: number};
};

/** Composer package info from the Composer API. */
export type ComposerPackage = {
    name: string;
    version: string;
    description: string;
    type: string;
};

/** SSE event types. */
export type SSEEventType = 'debug-updated';

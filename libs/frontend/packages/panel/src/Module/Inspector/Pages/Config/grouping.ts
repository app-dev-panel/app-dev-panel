export type NamespaceGroup<T> = {name: string; displayName: string; entries: T[]};

const SERVICES_GROUP = '';
const SERVICES_LABEL = 'Services';

function namespaceKey(id: string): string {
    if (!id.includes('\\')) return SERVICES_GROUP;
    const parts = id.split('\\');
    if (parts.length <= 2) return parts[0];
    return parts.slice(0, 2).join('\\');
}

export function groupByNamespace<T extends {id: string}>(entries: T[]): NamespaceGroup<T>[] {
    const groups = new Map<string, T[]>();
    for (const entry of entries) {
        const key = namespaceKey(entry.id);
        const bucket = groups.get(key);
        if (bucket) {
            bucket.push(entry);
        } else {
            groups.set(key, [entry]);
        }
    }
    return [...groups.entries()]
        .sort(([a], [b]) => {
            if (a === SERVICES_GROUP) return 1;
            if (b === SERVICES_GROUP) return -1;
            return a.localeCompare(b);
        })
        .map(([name, entries]) => ({name, displayName: name === SERVICES_GROUP ? SERVICES_LABEL : name, entries}));
}

export function stripNamespace(id: string, groupName: string): string {
    if (!groupName) return id;
    const prefix = groupName + '\\';
    if (id.startsWith(prefix)) return id.slice(prefix.length);
    return id;
}

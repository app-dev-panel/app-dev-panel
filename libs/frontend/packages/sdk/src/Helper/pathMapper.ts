/**
 * Maps file paths between remote (container/VM) and local (host) environments.
 *
 * Used in Docker/Vagrant setups where the application runs with different
 * paths than the developer's host machine.
 */

/**
 * Map a remote (container) path to a local (host) path.
 * First matching prefix wins.
 */
export const mapToLocal = (path: string, rules: Record<string, string>): string => {
    for (const [remote, local] of Object.entries(rules)) {
        if (path.startsWith(remote)) {
            return local + path.slice(remote.length);
        }
    }
    return path;
};

/**
 * Map a local (host) path to a remote (container) path.
 * First matching prefix wins.
 */
export const mapToRemote = (path: string, rules: Record<string, string>): string => {
    for (const [remote, local] of Object.entries(rules)) {
        if (path.startsWith(local)) {
            return remote + path.slice(local.length);
        }
    }
    return path;
};

/**
 * Map a path that may include a trailing line number suffix (e.g. "/app/Foo.php:42").
 * Applies mapping only to the path portion, preserving the suffix.
 */
export const mapToLocalWithLine = (pathWithLine: string, rules: Record<string, string>): string => {
    const match = pathWithLine.match(/^(.+?)([#:]\d+(?:-\d+)?)$/);
    if (match) {
        return mapToLocal(match[1], rules) + match[2];
    }
    return mapToLocal(pathWithLine, rules);
};

/**
 * Create a path mapper function bound to specific rules.
 */
export const createPathMapper = (rules: Record<string, string>) => ({
    toLocal: (path: string) => mapToLocal(path, rules),
    toRemote: (path: string) => mapToRemote(path, rules),
    toLocalWithLine: (pathWithLine: string) => mapToLocalWithLine(pathWithLine, rules),
    hasRules: Object.keys(rules).length > 0,
    rules,
});

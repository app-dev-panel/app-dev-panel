const SESSION_KEY = 'acp-session-id';

/**
 * Get or create an ACP session ID for this browser tab.
 * Stored in sessionStorage so each tab gets its own session.
 */
export const getAcpSessionId = (): string => {
    let id = sessionStorage.getItem(SESSION_KEY);
    if (!id) {
        id = crypto.randomUUID();
        sessionStorage.setItem(SESSION_KEY, id);
    }
    return id;
};

export const clearAcpSessionId = (): void => {
    sessionStorage.removeItem(SESSION_KEY);
};

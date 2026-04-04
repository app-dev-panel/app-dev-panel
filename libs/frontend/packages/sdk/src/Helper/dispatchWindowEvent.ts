export type CrossWindowEventType = 'router.navigate' | 'panel.loaded';
export type CrossWindowValueType = string | null | number | boolean;

export const dispatchWindowEvent = (targetWindow: Window, event: CrossWindowEventType, value: CrossWindowValueType) => {
    // Use '*' as targetOrigin because the toolbar (parent) and the panel (iframe)
    // can be on different origins (e.g., app on localhost:8101, debug server on 127.0.0.1:8080).
    // This is safe for a development tool — no sensitive data is transmitted.
    targetWindow.postMessage({event, value}, '*');
};

export type CrossWindowEventType = 'router.navigate' | 'panel.loaded';
export type CrossWindowValueType = string | null | number | boolean;

export const dispatchWindowEvent = (targetWindow: Window, event: CrossWindowEventType, value: CrossWindowValueType) => {
    targetWindow.postMessage({event, value}, window.location.origin);
};

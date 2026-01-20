export type CrossWindowEventType = 'router.navigate' | 'panel.loaded';
export type CrossWindowValueType = string | null | number | boolean;

export const dispatchWindowEvent = (window: Window, event: CrossWindowEventType, value: CrossWindowValueType) => {
    window.postMessage({event, value}, '*');
};

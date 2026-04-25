import {MouseEvent} from 'react';

/**
 * If the click has Ctrl (Linux/Windows) or Cmd (macOS) held down, open `url`
 * in a new tab with `noopener,noreferrer`, stop event propagation and default,
 * and return `true`. Otherwise return `false` without touching the event, so
 * the caller can run its normal-click behaviour.
 */
export const openInNewTabOnModifier = (event: MouseEvent, url: string): boolean => {
    if (!(event.ctrlKey || event.metaKey)) return false;
    window.open(url, '_blank', 'noopener,noreferrer');
    event.stopPropagation();
    event.preventDefault();
    return true;
};

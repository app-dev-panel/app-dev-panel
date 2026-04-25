import type {ChatContext} from '@app-dev-panel/sdk/API/Llm/Llm';

/**
 * Collects browser-side context to attach to an AI chat message.
 *
 * Used by the chat panel to give the LLM implicit context about the current
 * page (URL, selected debug entry, collector) and the user's environment
 * (locale, timezone, viewport, theme). This is injected into the system/dev
 * prompt on the backend and is not shown to the user.
 */
export const collectChatContext = (): ChatContext => {
    if (typeof window === 'undefined') {
        return {};
    }

    const context: ChatContext = {
        url: window.location.href,
        userAgent: window.navigator.userAgent,
        language: window.navigator.language,
        viewport: {width: window.innerWidth, height: window.innerHeight},
        screen: {width: window.screen.width, height: window.screen.height, devicePixelRatio: window.devicePixelRatio},
        theme: window.matchMedia?.('(prefers-color-scheme: dark)').matches ? 'dark' : 'light',
        title: document.title,
    };

    try {
        context.timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    } catch {
        // Intl may be unavailable in some environments; skip silently.
    }

    if (document.referrer !== '') {
        context.referrer = document.referrer;
    }

    return context;
};

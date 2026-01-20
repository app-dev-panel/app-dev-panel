export function scrollToAnchor(offset = 450, anchor?: string) {
    setTimeout(() => {
        const elementId = anchor || window.location.hash?.replace('#', '');
        const elementToScroll = document.getElementById(elementId);

        if (!elementToScroll) {
            return;
        }

        window.scrollTo({
            top: elementToScroll.offsetTop - offset,
            behavior: 'smooth',
        });
    });
}

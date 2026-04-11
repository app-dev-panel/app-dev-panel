<!--
  ADP cover layout.

  Why override Slidev's built-in:
    1. pages/01-title.md ships with `class: text-white` in its frontmatter.
       That would make the title invisible on our white/light card.
    2. pages/01-title.md ships with `background: '#0F172A'` which is ignored
       by the stock cover layout, but we want to make DOUBLY sure the slide
       renders on the ADP background.

  Structure: a left-aligned vertical stack that places the title in the
  upper-middle and everything else below it. No gradient-clip tricks - the
  title is painted in solid ADP primary, the subtitle sits in the ADP
  secondary text color, and everything inherits the Inter font.
-->
<template>
    <div class="slidev-layout cover">
        <div class="adp-cover-inner">
            <slot />
        </div>
    </div>
</template>

<style scoped>
.slidev-layout.cover {
    padding: 0 !important;
    background: var(--adp-bg-default) !important;
    color: var(--adp-text-primary) !important;
    display: grid !important;
    place-content: center start !important;
    height: 100%;
}

.adp-cover-inner {
    padding: 4rem 5rem;
    max-width: 58rem;
}
</style>

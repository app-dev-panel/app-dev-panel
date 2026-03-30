---
title: Поддержать ADP
---

# Поддержать ADP

ADP -- проект с открытым исходным кодом, который поддерживается в свободное время. Ваша поддержка помогает покрывать затраты на инфраструктуру, финансировать новые возможности и обеспечивать активную разработку.

## Зачем поддерживать?

- **Устойчивость** -- Регулярные взносы обеспечивают долгосрочную поддержку и своевременные обновления
- **Разработка функций** -- Спонсоры напрямую финансируют новые коллекторы, адаптеры и улучшения UI
- **Приоритетная поддержка** -- Спонсоры получают более быстрые ответы на issues и запросы фич
- **Рост сообщества** -- Ваша поддержка помогает строить более сильную экосистему вокруг ADP

## Как поддержать

### Регулярные платежи

<div class="sponsor-links">
  <a href="https://www.patreon.com/xepozz" target="_blank" rel="noopener" class="sponsor-link">
    <span class="sponsor-link-name">Patreon</span>
    <span class="sponsor-link-url">patreon.com/xepozz</span>
  </a>
  <a href="https://buymeacoffee.com/xepozz" target="_blank" rel="noopener" class="sponsor-link">
    <span class="sponsor-link-name">Buy Me a Coffee</span>
    <span class="sponsor-link-url">buymeacoffee.com/xepozz</span>
  </a>
  <a href="https://boosty.to/xepozz" target="_blank" rel="noopener" class="sponsor-link">
    <span class="sponsor-link-name">Boosty</span>
    <span class="sponsor-link-url">boosty.to/xepozz</span>
  </a>
</div>

### Криптовалюта

<div class="crypto-addresses">
  <div class="crypto-row">
    <div class="crypto-label">USDT TON (Ton)</div>
    <div class="crypto-value">
      <code>UQDuFuRj_PgCMtV30FGLLlc51NzMGrGaHI8uhrkILw00D2UE</code>
      <CopyButton text="UQDuFuRj_PgCMtV30FGLLlc51NzMGrGaHI8uhrkILw00D2UE" />
    </div>
  </div>
  <div class="crypto-row">
    <div class="crypto-label">USDT TRC20 (Tron)</div>
    <div class="crypto-value">
      <code>THfZotbtgmHrFGhPvY2BFq7ALKhZtYWjPh</code>
      <CopyButton text="THfZotbtgmHrFGhPvY2BFq7ALKhZtYWjPh" />
    </div>
  </div>
  <div class="crypto-row">
    <div class="crypto-label">USDT ERC20 (Ethereum)</div>
    <div class="crypto-value">
      <code>0x923073361Da37E54443c364bA8fDB994B71D2083</code>
      <CopyButton text="0x923073361Da37E54443c364bA8fDB994B71D2083" />
    </div>
  </div>
</div>

## Корпоративное спонсорство

Хотите разместить логотип вашей компании на главной странице ADP и в документации? Корпоративное спонсорство начинается от **$500/месяц** и включает:

- Размещение логотипа на главной странице и в футере документации
- Ссылка на ваш сайт со всех страниц
- Упоминание в анонсах релизов
- Приоритетные запросы на новые функции

Свяжитесь по адресу **xepozzd@gmail.com** для получения подробностей.

## Текущие спонсоры

<div class="sponsors-section">
  <p class="sponsors-empty">Станьте первым спонсором и разместите свой логотип здесь!</p>
</div>

<style>
.sponsor-links {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin: 16px 0 24px;
}
.sponsor-link {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border: 1px solid var(--vp-c-divider);
    border-radius: var(--adp-radius);
    background: var(--vp-c-bg-soft);
    text-decoration: none !important;
    transition: all 0.2s ease;
}
.sponsor-link:hover {
    border-color: var(--vp-c-brand-1);
    box-shadow: var(--adp-shadow-sm);
    transform: translateY(-2px);
}
.sponsor-link-name {
    font-weight: 600;
    font-size: 16px;
    color: var(--vp-c-text-1);
}
.sponsor-link-url {
    font-size: 14px;
    color: var(--vp-c-brand-1);
}

.crypto-addresses {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin: 16px 0 24px;
}
.crypto-row {
    padding: 16px 20px;
    border: 1px solid var(--vp-c-divider);
    border-radius: var(--adp-radius);
    background: var(--vp-c-bg-soft);
}
.crypto-label {
    font-weight: 600;
    font-size: 14px;
    color: var(--vp-c-text-2);
    margin-bottom: 8px;
}
.crypto-value {
    display: flex;
    align-items: center;
    gap: 8px;
}
.crypto-value code {
    flex: 1;
    font-size: 13px;
    word-break: break-all;
    background: var(--vp-c-bg) !important;
    color: var(--vp-c-text-1) !important;
    padding: 6px 10px !important;
    border-radius: 6px;
    border: 1px solid var(--vp-c-divider);
}

.sponsors-empty {
    text-align: center;
    color: var(--vp-c-text-3);
    font-style: italic;
    padding: 32px;
    border: 2px dashed var(--vp-c-divider);
    border-radius: var(--adp-radius-lg);
}

@media (max-width: 640px) {
    .sponsor-link {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }
}
</style>

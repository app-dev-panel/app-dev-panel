---
layout: home
hero:
  name: ADP
  text: Application Development Panel
  tagline: Фреймворк-независимая панель отладки и разработки для PHP-приложений
  image:
    src: /duck.svg
    alt: Уточка ADP
  actions:
    - theme: brand
      text: Начать работу
      link: /ru/guide/getting-started
    - theme: alt
      text: GitHub
      link: https://github.com/app-dev-panel/app-dev-panel
    - theme: alt
      text: Блог
      link: /ru/blog/

features:
  - icon: 🔍
    title: Универсальная отладка
    details: Инспектируйте логи, события, запросы, исключения, SQL-запросы и многое другое — независимо от используемого PHP-фреймворка.
  - icon: 🔌
    title: Адаптеры фреймворков
    details: Готовая поддержка Symfony, Yii 2, Yii 3 и Laravel. Легко добавить свой адаптер.
  - icon: 📡
    title: Обновления в реальном времени
    details: Мгновенные обновления через Server-Sent Events. Данные отладки появляются в реальном времени.
  - icon: 🤖
    title: MCP Сервер
    details: Интеграция с AI-ассистентами через Model Context Protocol. Дайте AI-инструментам прямой доступ к данным отладки.
  - icon: 🎨
    title: Современный интерфейс
    details: Красивое React SPA с тёмной темой, поиском, фильтрами и адаптивным дизайном на Material-UI.
  - icon: ⚡
    title: Без конфигурации
    details: Работает через PSR-стандарты. Никакой кастомной настройки — просто установите адаптер и отлаживайте.
---

<div class="home-sponsors">
  <h2 class="home-sponsors-title">Спонсоры</h2>
  <div class="home-sponsors-grid">
    <p class="home-sponsors-empty">Ваш логотип здесь</p>
  </div>
  <div class="home-sponsors-cta">
    <a href="/ru/sponsor">Стать спонсором</a>
    <span class="home-sponsors-sep">|</span>
    <a href="https://github.com/app-dev-panel/app-dev-panel" target="_blank" rel="noopener">Star на GitHub</a>
  </div>
</div>

<style>
.home-sponsors {
    max-width: 800px;
    margin: 80px auto 0;
    text-align: center;
    padding: 0 24px;
}
.home-sponsors-title {
    font-size: 24px;
    font-weight: 700;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    color: var(--vp-c-text-2);
    margin-bottom: 24px;
}
.home-sponsors-grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 24px;
    margin-bottom: 24px;
}
.home-sponsors-empty {
    padding: 32px 48px;
    border: 2px dashed var(--vp-c-divider);
    border-radius: var(--adp-radius-lg);
    color: var(--vp-c-text-3);
    font-style: italic;
    min-width: 280px;
}
.home-sponsors-cta {
    font-size: 14px;
    color: var(--vp-c-text-3);
}
.home-sponsors-cta a {
    color: var(--vp-c-brand-1);
    text-decoration: none;
    font-weight: 500;
}
.home-sponsors-cta a:hover {
    text-decoration: underline;
}
.home-sponsors-sep {
    margin: 0 8px;
    color: var(--vp-c-divider);
}
</style>

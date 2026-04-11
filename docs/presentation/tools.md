# Инструменты для создания презентации из Markdown

Подборка вариантов для превращения `podlodka-php-plan.md` в реальные слайды. Разделены по категориям + рекомендация под технический доклад с кодом и живыми демо.

## 1. Веб-фреймворки (HTML-слайды в браузере)

| Инструмент | Движок | Плюсы | Минусы |
|---|---|---|---|
| **[Slidev](https://sli.dev/)** | Vue 3 + Vite | Красивый из коробки, shiki-подсветка с highlight-по-диффу, **Monaco-редактор с исполнением кода**, Vue-компоненты внутри MD, тема `seriph` выглядит как Stripe/Vercel, презентер-вью с камерой и таймером, запись в mp4 | Нужен Node, тяжелее Marp |
| **[Marp](https://marp.app/)** | Marpit | Самый простой pipeline: VS Code extension `Marp for VS Code` + CLI `marp-cli`, экспорт в HTML/PDF/PPTX/PNG одной командой, минималистичный синтаксис с `---` | Бедноват на анимации и интерактив |
| **[reveal.js](https://revealjs.com/)** | reveal.js | Эталон, максимум возможностей, вертикальные стеки слайдов, fragments | MD-поддержка слабее, больше ручной работы |
| **[remark](https://remarkjs.com/)** | remark.js | Один HTML-файл, ноль зависимостей, открыл — работает | Старенький, визуально скучный |
| **[mdx-deck](https://github.com/jxnblk/mdx-deck)** | MDX + React | React-компоненты прямо в слайдах | Проект почти заброшен |

## 2. CLI / TUI (презентация в терминале)

Идеально для программистских тусовок — выглядит очень «в теме».

| Инструмент | Язык | Фишки |
|---|---|---|
| **[presenterm](https://github.com/mfontanini/presenterm)** | Rust | Подсветка кода через syntect, **картинки прямо в терминале** (kitty/iterm/sixel), mermaid, latex, speaker notes, исполнение сниппетов. Сейчас самый живой проект в нише |
| **[slides](https://github.com/maaslalani/slides)** | Go | Очень простой, умеет **исполнять код из слайда и показывать вывод** |
| **[patat](https://github.com/jaspervdj/patat)** | Haskell | На базе Pandoc, поэтому «ест» почти любой MD-диалект |
| **[lookatme](https://github.com/d0c-s4vage/lookatme)** | Python | Плагины, темы, расширяется |

## 3. Pandoc — швейцарский нож

Если уже есть `.md`, **Pandoc** умеет конвертировать в что угодно без отдельного инструмента:

```bash
# reveal.js HTML
pandoc talk.md -t revealjs -s -o talk.html \
  -V revealjs-url=https://unpkg.com/reveal.js@5

# LaTeX Beamer → PDF
pandoc talk.md -t beamer -o talk.pdf

# PowerPoint (да, работает)
pandoc talk.md -o talk.pptx
```

- **Плюс**: нулевая привязка к инструменту, та же исходка идёт в блог и в `llms.txt`.
- **Минус**: дефолтные темы посредственные, красиво — только если поднастроить.

## 4. Интегрированные / заметочные системы

- **[Quarto](https://quarto.org/)** — академический подход (от создателей RStudio), `.qmd` → reveal.js/beamer/pptx, поддержка executable-блоков на Python/R/Julia, supports Observable. Мощный, если нужна воспроизводимая презентация с графиками.
- **[Obsidian Advanced Slides](https://github.com/MSzturc/obsidian-advanced-slides)** — если ты уже в Obsidian, слайды прямо из заметок.
- **[HedgeDoc](https://hedgedoc.org/)** / **[HackMD](https://hackmd.io)** — коллаборативный MD-редактор + slide-mode на reveal.js, удобно готовить доклад командой.

---

## Рекомендация для доклада про ADP

Исходя из контента (`podlodka-php-plan.md`): много кода PHP + TypeScript, два live-демо (Inspector, MCP), желание «выглядеть технично».

### Основной выбор: Slidev

Причины под эту конкретную задачу:

1. **Shiki magic-move / twoslash** — можно эффектно подсвечивать, как одна и та же строчка кода мутирует (идеально для блока «как один и тот же `LoggerInterfaceProxy` встаёт в Symfony / Laravel / Yii2»).
2. **Monaco-редактор в слайде** — можно прямо со слайда запускать пример (для блока PSR-прокси это выглядит очень сильно).
3. **`<iframe>` компонент** — встроишь в слайд работающий ADP Panel с `localhost:8101`, и это и есть твоё живое демо без переключения в браузер. Один Cmd+Tab меньше — один шанс не промахнуться.
4. **Пресентер-вью** с заметками и таймером — удобно, когда надо держать 22 минуты.
5. **Экспорт в PDF** для раздачи — одной командой `slidev export`.

#### Быстрый старт

```bash
npm init slidev@latest podlodka-adp
cd podlodka-adp
npm run dev   # → http://localhost:3030
```

#### Мини-пример `slides.md`

~~~markdown
---
theme: seriph
title: Как мы делали фреймворконезависимую дебаг-панель
---

# ADP
Фреймворконезависимая дебаг-панель для PHP

---

# PSR-прокси

```ts {1|3-5|all}
$logger->info('hello');
// ↓
class LoggerInterfaceProxy implements LoggerInterface {
    use ProxyDecoratedCalls;
}
```
~~~

### Запасной вариант: Marp

Бери, если не хочется возиться с Node. VS Code extension + `Ctrl+Shift+P → Marp: Export Slide Deck` и готовый PDF за 2 минуты. Подойдёт, если доклад более «бизнесовый» и слайды спокойные.

### Не рекомендую для Подлодки

**presenterm** — если хочется пошалить и сделать «терминальный» доклад, но для Подлодки с проектором я бы не рисковал: подсветка кода в терминале зависит от того, что организаторы подключат. Оставь на дружеский митап.

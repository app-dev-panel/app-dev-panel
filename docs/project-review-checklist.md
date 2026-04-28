# Project Review Checklist

End-to-end ревью репозитория ADP. Идти сверху вниз: сначала «здоровье» репо
(тесты, линтеры, граф зависимостей), потом каждый модуль, плейграунды,
фронтенд, документация и инфраструктура. Каждый пункт — независимая проверка
с понятным критерием «прошло / не прошло».

## 0. Предусловия

- [ ] `make install` завершился без ошибок (PHP + frontend + playgrounds).
- [ ] `php -m | grep pcov` — драйвер покрытия установлен.
- [ ] Chrome + ChromeDriver одной мажорной версии (для frontend e2e).
- [ ] Текущая ветка чистая (`git status`), синхронизирована с `master`.

## 1. Глобальное здоровье репозитория

- [ ] `make all` зелёный (= `make check && make test`). Любой `S/I/R/D/N/W`
      маркер PHPUnit — это FAIL, см. CLAUDE.md «Zero Tolerance».
- [ ] `make modulite` — 0 нарушений. Проверить что новых записей в
      `modulite.php` нет без обоснования.
- [ ] `make mago` — pass с актуальным baseline. Записать число подавлений
      в `mago-lint-baseline.php` и сравнить с предыдущим ревью.
- [ ] Пройтись по таймаутам в `Makefile`/`phpunit.xml.dist`/`vitest.config.ts`
      — соответствуют таблице в CLAUDE.md, никто не поднимал лимиты.
- [ ] `composer.lock` и `libs/frontend/package-lock.json` закоммичены и
      соответствуют `composer.json` / `package.json`.
- [ ] `git grep -nE "markTestSkipped|markTestIncomplete|expectDeprecation"`
      — вне разрешённых мест ничего не появилось.
- [ ] `git grep -nE "TODO|FIXME|XXX|HACK"` — короткий список, у каждого есть
      контекст или issue.

## 2. Kernel (`libs/Kernel`)

- [ ] `libs/Kernel/CLAUDE.md` отражает текущий состав коллекторов и API.
- [ ] Все коллекторы реализуют `CollectorInterface`, `final class` где можно.
- [ ] `Storage` пишет атомарно (tmp + rename), путь к storage валидируется.
- [ ] `Debugger` flush идемпотентен; повторный вызов не дублирует данные.
- [ ] Прокси (Logger / EventDispatcher / HttpClient / Container) не ломают
      контракты PSR при отключённом дебаге.
- [ ] Юнит-тесты покрывают каждый коллектор; покрытие не ниже отметки
      в CLAUDE.md (Kernel ≥ 85%).
- [ ] Нет утечки сторонних `use` (Yii/Symfony/Laravel) — Kernel framework-agnostic.

## 3. API (`libs/API`)

- [ ] Все ответы обёрнуты в `{id, data, error, success, status}`.
- [ ] Каждый endpoint имеет тест в `libs/API/tests` (контроллер + middleware).
- [ ] SSE: heartbeat-интервал, корректное закрытие соединения, нет
      бесконечных циклов без timeout.
- [ ] CORS / allowlist / auth — секурно по умолчанию (см. `docs/tasks/p1-security.md`).
- [ ] OpenAPI (`openapi/inspector.yaml`, `openapi/ingestion.yaml`)
      синхронизирован с реальными контроллерами.
- [ ] `RequestController` / `LlmController` — таймауты соответствуют CLAUDE.md.

## 4. McpServer (`libs/McpServer`)

- [ ] stdio и HTTP транспорты покрыты тестами.
- [ ] Список экспортируемых tools в коде совпадает с `website/guide/mcp-server.md`.
- [ ] `InspectorClient` не делает HTTP-запросов без timeout.
- [ ] Нет прямого доступа к `libs/Adapter/*` — только через `kernel`.

## 5. Cli (`libs/Cli`)

- [ ] Все команды в `bin/` зарегистрированы и имеют `--help`.
- [ ] `FrontendUpdateCommand` — таймауты GitHub release / ZIP соответствуют CLAUDE.md.
- [ ] `make serve-*` поднимают плейграунды на правильных портах
      (`8101`–`8105`).
- [ ] Каждая команда имеет smoke-тест (input → exit code).

## 6. Адаптеры (`libs/Adapter/*`)

Для каждого адаптера (Yii3, Symfony, Laravel, Yii2, Spiral, Cycle):

- [ ] CLAUDE.md адаптера соответствует коду (DI, события, middleware).
- [ ] Прокси регистрируются через нативный механизм фреймворка
      (compiler pass / service provider / module bootstrap).
- [ ] Нет прямого `require`/`use` других адаптеров.
- [ ] Покрытие тестами не ниже значения в таблице CLAUDE.md
      (Symfony ≥ 98%, Yii2 ≥ 57%).
- [ ] `make mago-playground-<framework>` зелёный.
- [ ] `make test-fixtures-<framework>` проходит против запущенного плейграунда.
- [ ] Установочные инструкции в `website/guide/adapters/*.md` актуальны
      (composer require, конфиг, проверка `/_adp`).

Особое внимание:
- [ ] Yii2 — низкое покрытие (57%), точечно поднять.
- [ ] Cycle — только schema, не должен зависеть от Kernel.

## 7. Testing (`libs/Testing`)

- [ ] `FixtureRunner` — HTTP timeout ≤ 15s.
- [ ] `DebugDataFetcher` — retry deadline ≤ 15s.
- [ ] Все фикстуры под `tests/` имеют детерминированные ассерты,
      нет зависимости от ambient state (env, network).
- [ ] CLI команда `adp:fixture` работает против всех плейграундов.

## 8. FrontendAssets (`libs/FrontendAssets`)

- [ ] `dist/` пустой в master (наполняется на релизе).
- [ ] Split-repo flow в CLAUDE.md соответствует тегам в `app-dev-panel/frontend-assets`.
- [ ] `composer require` ставит последний релиз, не dev-версию.

## 9. Frontend (`libs/frontend`)

- [ ] `npm run build` зелёный для `panel`, `toolbar`, `sdk`.
- [ ] `make test-frontend` — 328 тестов, без падений.
- [ ] `make test-frontend-e2e` — 4 browser-сьюта проходят.
- [ ] `npm run check` — Prettier + ESLint без warnings.
- [ ] Strict TypeScript: `tsc --noEmit` без ошибок в каждом пакете.
- [ ] Storybook (если есть) собирается; визуально проверить ключевые страницы.
- [ ] Нет прямых импортов между пакетами кроме через `sdk`.
- [ ] A11y-чеки: семантические landmark-теги, focus management в модалках.

## 10. Плейграунды (`playground/*`)

- [ ] Каждый плейграунд стартует через `make serve-*` без ошибок.
- [ ] `/_adp` отдаёт SPA, `/_adp/api/...` отвечает JSON.
- [ ] В каждом плейграунде вызывается полный набор коллекторов (logger,
      events, http, db) — проверить через `make fixtures-*`.
- [ ] Установка из чистого состояния воспроизводима: README плейграунда
      работает «из коробки».

## 11. Документация

### `website/` (VitePress, источник правды)

- [ ] `npm run build` без warnings, `dist/llms.txt` и `llms-full.txt` сгенерировались.
- [ ] Sidebar/nav в `.vitepress/config.ts` соответствуют файлам в `guide/` и `api/`.
- [ ] EN и RU локали синхронизированы по структуре (страницы, заголовки, anchors).
- [ ] Скриншоты в `guide/*` соответствуют текущему UI.
- [ ] Блог: даты, авторы, теги — корректны.
- [ ] `website/guide/feature-matrix.md` — отражает реальный статус каждого адаптера.

### `CLAUDE.md` файлы

- [ ] Корневой `CLAUDE.md` — таблицы покрытия и таймаутов актуальны.
- [ ] Каждый `libs/*/CLAUDE.md` — внутренняя архитектура соответствует коду.
- [ ] `docs/` — устаревшие планы перенесены в архив или закрыты.

## 12. CI / GitHub

- [ ] Workflows в `.github/workflows/` — матрица PHP 8.4/8.5 × Linux/Windows.
- [ ] PR-комментарии (coverage + Mago) приходят на тестовый PR.
- [ ] Нет `continue-on-error: true` на критичных шагах.
- [ ] Кэши actions имеют разумные ключи (lockfile-based).
- [ ] Защита веток: `master` требует review + green CI.

## 13. Безопасность

- [ ] `docs/tasks/p1-security.md` — пройтись по каждому пункту, отметить статус.
- [ ] Запустить `/security-review` на текущей ветке.
- [ ] Default config: панель не открыта наружу (`localhost`-only / token).
- [ ] SSE / API не отдают чувствительные ENV в payload.
- [ ] Path traversal в storage / inspector подавлен (валидация имени файла).

## 14. Финальная сверка

- [ ] `make all` зелёный последний раз.
- [ ] `git diff master..HEAD` — каждый коммит атомарен, сообщения по конвенции.
- [ ] Обновлены `docs/tasks/*.md`: пройденные пункты помечены `[x]`.
- [ ] Открыт follow-up issue на всё, что не закрыли в этот заход.

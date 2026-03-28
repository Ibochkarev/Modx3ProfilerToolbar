# История изменений

Краткая история версий Modx3ProfilerToolbar. Детали по версиям см. в [core/components/modx3profilertoolbar/docs/changelog.txt](../core/components/modx3profilertoolbar/docs/changelog.txt).

---

## 1.0.0-pl (текущая)

### Основное

- **Modx3ProfilerToolbar** — только Frontend Dev Toolbar для MODX 3. Без админки, без хранения трейсов, без debug-отчёта.
- **Frontend Dev Toolbar** (Vue Petite): панель в правом нижнем углу, вкладки Overview / Slow Components / Timeline / SQL; данные текущего запроса встраиваются в HTML (`window.modx3profilertoolbarToolbarData`).
- **Advisor:** правила UncachedSnippetRule, N1QueryRule, SlowQueryRule, ChunkOveruseRule, TooManySnippetsRule; рекомендации отображаются в тулбаре.
- **Production-safe:** sampling 1–100%, тулбар только при входе в Manager; overhead < 5%.

### Локализация

- Тексты тулбара из лексиконов MODX (ru, en, uk).

### Требования

- PHP 8.1+, MODX Revolution 3.x. VueTools и админка не требуются.

---

При изменении функционала обновляйте этот файл и `core/components/modx3profilertoolbar/docs/changelog.txt`.

## Проблема
При включённом фронтенд-тулбаре профайлер корректно рассчитывал **performance_score** и **advisor** (рекомендации по производительности), но эти данные не выводились в интерфейс.

## Решение
Добавлен вывод **Performance Score** и отдельная вкладка **Advisor** в Vue‑тулбар.

### Изменения

**ToolbarService** (backend)
- Добавлены `advisor` и `performance_score` в массив данных `$toolbarData`

**toolbar.js** (frontend)
- Добавлена обработка `advisor` и `performance_score` из `window.modx3profilertoolbarToolbarData`
- Добавлена новая вкладка **Advisor** со списком рекомендаций (message + suggestion)
- Добавлен блок **Performance Score** в Overview: `85 / 100 (Excellent)`
- Добавлены методы `getScoreClass()` и `getScoreLabel()`

**toolbar.css**
- Стили для `.mpr-metric--score` с цветовой индикацией (зелёный/жёлтый/красный) и свечением
- Стили для вкладки Advisor: `.mpr-advisor-item`, `.mpr-advisor-message`, `.mpr-advisor-suggestion`

### Лексикон
Переводы уже присутствуют:
- `mpr_advisor` — Advisor / Рекомендации / Рекомендації
- `mpr_performance_score` — Performance Score / Оценка производительности / Оцінка продуктивності
- `mpr_status_excellent` — Excellent / Отлично / Відмінно
- `mpr_status_fair` — Fair / Удовлетворительно / Задовільно
- `mpr_status_critical` — Critical / Критично / Критично

## Closes
Fixes #3

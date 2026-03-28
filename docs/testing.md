# Проверка Modx3ProfilerToolbar

Автоматические тесты не используются. Рекомендуется ручная проверка после установки и при смене настроек.

---

## Ручная проверка

1. **Включение и sampling:** включите **modx3profilertoolbar.enabled** и **modx3profilertoolbar.frontend_toolbar**, откройте страницу в web — должен появиться тулбар. Поставьте **modx3profilertoolbar.sampling_rate** = 50 — тулбар будет показываться не на каждом запросе.
2. **Тулбар:** при входе в Manager и открытии страницы в правом нижнем углу — панель с метриками, вкладки Overview / Slow Components / Timeline / SQL.
3. **Пороги:** задайте **modx3profilertoolbar.slow_snippet_ms** и **modx3profilertoolbar.slow_query_ms** — в тулбаре рекомендации Advisor должны учитывать эти пороги.
4. **Отключение:** **modx3profilertoolbar.enabled** = Нет — тулбар не отображается.

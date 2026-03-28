# Toolbar Enhancement Changelog

## Исправления в тулбаре фронтенда

### 1. **Удалены лишние данные из Overview**
**Убрано из блока info:**
- `queries` — дублирует метрику SQL count
- `totalTime` — дублирует метрику Total time
- `phpTime` — дублирует метрику PHP

**Оставлено только:**
- `queryTime` — время SQL запросов (в секундах)
- `source` — источник данных (database/cache)

### 2. **Новая структура Overview**

**Было:**
```
┌─────────────────────┐
│ queries: 1          │
│ totalTime: 2.6s     │
│ queryTime: 0.06s    │
│ phpTime: 2.5s       │
│ source: database    │
├─────────────────────┤
│ Total time: 2600 ms │
│ TTFB: 2600 ms       │
│ PHP: 2539 ms        │
│ SQL: 61 ms          │
└─────────────────────┘
```

**Стало:**
```
┌─────────────────────┐
│ Total time: 2600 ms │ ← с цветным индикатором
│ TTFB: 2600 ms       │
│ PHP: 2539 ms        │
│ SQL: 61 ms          │
├─────────────────────┤
│ Query time: 0.06s   │
│ Source: database    │
└─────────────────────┘
```

### 3. **Добавлена полная локализация**

Все тексты теперь берутся из лексиконов MODX:

**Backend (ToolbarService.php):**
- Добавлен метод `getLexicon()` — формирует массив переводов
- Лексикон передаётся в `window.modx3profilertoolbarToolbarData.lexicon`

**Frontend (toolbar.js):**
- Все хардкод-тексты заменены на `lex.key`
- Названия вкладок: `lex.overview`, `lex.slow_components`, `lex.timeline`, `lex.sql`
- Метрики: `lex.total_time`, `lex.ttfb`, `lex.php`, `lex.sql_time`
- Компактный вид: `lex.snippets`, `lex.sql_queries`
- SQL: `lex.time`, `lex.calls`, `lex.possible_n1`
- Footer: `lex.open_trace`

**Добавленные ключи лексикона:**
```php
// Toolbar (ru/en/uk)
'mpr_slow_components' => 'Медленные компоненты',
'mpr_total_time' => 'Общее время',
'mpr_ttfb' => 'TTFB',
'mpr_php' => 'PHP',
'mpr_sql_time' => 'SQL',
'mpr_memory' => 'Память',
'mpr_snippets' => 'сниппетов',
'mpr_sql_queries' => 'SQL',
'mpr_query_time' => 'Время запросов',
'mpr_source' => 'Источник',
'mpr_open_trace' => 'Открыть полный трейс в админке',
'mpr_time' => 'Время',
'mpr_calls' => 'Вызовов',
'mpr_cached' => 'кэш',
'mpr_possible_n1' => 'Возможная проблема N+1',
```

### 4. **Улучшения UX**

**Компактный вид:**
- Используются лексиконные сокращения (`сниппетов` вместо `snip`)

**Expanded вид:**
- Заголовок локализован: `Performance Profiler` → `Профайлер производительности`
- Вкладки на родном языке
- Метки метрик понятны на любом языке

**Slow Components:**
- Вывод с локализацией: `5 вызовов` вместо `5 calls`
- Cached: `(кэш: да)` вместо `(cached: yes)`

**SQL:**
- `Время: 10 ms` / `Вызовов: 3` / `Возможная проблема N+1`

## Файлы

**Backend:**
- `core/components/modx3profilertoolbar/src/Service/ToolbarService.php`
  - Убраны лишние поля из `info`
  - Добавлен метод `getLexicon()`

**Frontend:**
- `assets/components/modx3profilertoolbar/js/toolbar/toolbar.js`
  - Переменная `lex` с переводами
  - Все тексты через `lex.key`

**Локализация:**
- `core/components/modx3profilertoolbar/lexicon/ru/default.inc.php`
- `core/components/modx3profilertoolbar/lexicon/en/default.inc.php`
- `core/components/modx3profilertoolbar/lexicon/uk/default.inc.php`

## Результат

Тулбар теперь:
- ✅ Чище: нет дублирования данных
- ✅ Информативнее: `queryTime` и `source` в удобном месте
- ✅ Полностью локализован (ru/en/uk)
- ✅ Автоматически подхватывает язык MODX
- ✅ Fallback на английский при отсутствии перевода

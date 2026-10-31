## Проблема
При включённой настройке `modx3profilertoolbar.cli_enabled` профайлер собирает данные в CLI‑режиме (cron, скрипты), но не выводит их никуда. Тулбар показывается только в web‑контексте, поэтому для CLI‑запросов метрики терялись.

## Решение
Добавлена полноценная поддержка вывода профилирования в CLI‑режиме с гибкой настройкой формата и места назначения.

## Новые системные настройки
| Настройка | Значение по умолчанию | Описание |
|-----------|----------------------|----------|
| `cli_output` | `console` | Куда выводить результаты: `console` (stdout) или `file` |
| `cli_output_file` | `{core_path}components/modx3profilertoolbar/logs/cli-trace.log` | Путь к файлу для сохранения трейсов |
| `cli_output_format` | `json` | Формат вывода: `json`, `text` или `csv` |

## Изменения в коде
### ProfilerService
- `outputCliTrace()` – главный метод вывода, выбирает формат и место назначения
- `formatTraceAsText()` – человекочитаемый отчёт с метриками, оценкой производительности, советами, событиями и SQL
- `formatTraceAsCsv()` – экспорт в CSV (RFC 4180)
- `formatBytes()` – форматирование байтов (B, KB, MB, GB)
- `escapeCsvField()` – экранирование спецсимволов в CSV
- Конструктор обновлён для чтения новых настроек
- Метод `stop()` теперь вызывает `outputCliTrace()` в CLI-режиме

### Безопасность
- Валидация путей: файлы записываются только внутри `core_path`
- Блокировка обхода директорий (directory traversal)
- Автоматическое создание директорий
- Fallback в безопасную директорию при ошибке валидации

### Лексиконы
Добавлены переводы для новых настроек:
- English (lexicon/en/default.inc.php)
- Русский (lexicon/ru/default.inc.php)
- Українська (lexicon/uk/default.inc.php)

## Примеры использования
### Вывод в консоль (JSON)
```bash
php /path/to/modx/script.php
```

Вывод:
```json
{
  "request_id": "abc123...",
  "metrics": { ... },
  "performance_score": 85,
  "advisor": [ ... ],
  "events": [ ... ],
  "sql": [ ... ]
}
```

### Вывод в файл (текст)
Настройки:
- cli_output = file
- cli_output_file = logs/cli-trace.log
- cli_output_format = text

Вывод:
```
--- Modx3ProfilerToolbar CLI Trace ---
Request ID: abc123...
Context: cli
URL: /cli/test
Method: CLI

Metrics:
  Total Time:    125 ms
  PHP Time:      100 ms
  SQL Time:      25 ms
  Memory Peak:   8 MB
  ...

Performance Score: 85 / 100 (Excellent)

Advisor:
  - Performance is good

Events (2):
  - [event] OnMODXInit: 50 ms
  - [snippet] TestSnippet: 30 ms

SQL Queries (1):
  #1 (15 ms): SELECT * FROM modx_site_content WHERE id = 1
```

## Тестирование
- Добавлены unit-тесты в `tests/test_cli_unit.php`
- 33 проверки пройдены успешно
- Синтаксическая проверка: `php -l` – ошибок нет

## Совместимость
- PHP 8.0+
- MODX Revolution 3.x
- Не требует изменений в существующих настройках (обратная совместимость сохранена)

## Closes
Fixes #7

# Ограничения профилирования pdoTools в MODX 3.x

## Текущее состояние (февраль 2026)

### ✅ Что профилируется

| Тип | Статус | Примечание |
|-----|--------|-----------|
| **Обычные MODX-сниппеты** | ✅ Работает | `[[!mySnippet]]` — профилируются полностью |
| **Чанки через MODX-тег** | ✅ Работает | `[[$chunkName]]` — вызов через парсер MODX, профилируются |
| **SQL-запросы** | ✅ Работает | Все запросы любого типа |
| **MODX-события** | ✅ Работает | OnDocFormSave, OnResourceDelete и т.д. |
| **Память и время** | ✅ Работает | Общая статистика |
| **pdoTools-сниппеты** | ⚠️ Ограниченно | Только как обычные теги — без внутренней детализации |

### ⚠️ Особенности pdoTools-сниппетов

pdoTools-сниппеты (`msProducts`, `pdoResources`, `pdoPage`) **профилируются**, но:

1. **Нет детализации Fenom-чанков внутри `tpl`/`tplWrapper`**:
   - Сниппет появится в отчёте с общим временем
   - SQL-запросы внутри будут видны
   - Но чанки, переданные в `tpl=` (inline или файловые), не разделяются как отдельные элементы

2. **Нет профилирования модификаторов Fenom**:
   - `{$var|date_format:'%d.%m.%Y'}` не профилируется отдельно

3. **Нет профилирования вложенных вызовов pdoTools через `{$_modx->runSnippet()}`**:
   - `{$_modx->runSnippet('pdoMenu', [...])}` в чанке не детализируется

### Примеры вызовов на Fenom (что не детализируется)

В чанках и `tpl` pdoTools используется синтаксис Fenom. Отдельно по времени **не** профилируются:

```fenom
{* Модификаторы — один общий блок на весь чанк *}
{$pagetitle|escape}
{$price|number_format:0:'.':''}
{$publishedon|date_format:'%d.%m.%Y'}
{$image|phpthumbof:'w=200&h=200&zc=1'}

{* Вложенные сниппеты из Fenom — в отчёте только родительский тег *}
{$_modx->runSnippet('pdoMenu', [
   'parents' => 0,
   'level' => 2,
   'tplOuter' => '@INLINE <ul class="nav">{$wrapper}</ul>'
])}
{$_modx->runSnippet('pdoResources', [
   'parents' => 10,
   'limit' => 5,
   'tpl' => '@INLINE <div>{$pagetitle}</div>'
])}

{* include — подключаемый чанк не идёт отдельной строкой в Timeline *}
{include 'file:tpl/productRow.tpl'}

{* Циклы и условия — время считается в общем времени родительского сниппета *}
{foreach $products as $product}
   <div>{$product.pagetitle} — {$product.price|number_format:0}</div>
{/foreach}
{if $total > 0}
   {$total|number_format:0}
{/if}
```

В тулбаре будет виден только верхнеуровневый вызов, например `[[!msProducts]]` или `[[!pdoPage]]`, с общим временем и SQL; разбивки по модификаторам, `runSnippet` и `include` внутри Fenom нет.

### Пример отчёта

**Страница с `[[!msProducts]]` (8 товаров)**:
```
Snippets: 1
- [[!msProducts? &limit=`8` ...]] — 245.67 ms, 12 SQL-запросов

SQL Queries: 12
- SELECT ... FROM modx_ms2_products ... — 18.45 ms
- ...
```

**НЕ будет видно:**
- Сколько времени занял `tpl.msProductRowTpl.tpl` на каждый товар
- Сколько раз вызывался `{$_modx->makeUrl()}`

## Причина ограничений

### Архитектура pdoTools 3.x

1. **Внутренний кеш `CoreTools::$tags` не заполняется**:
   - В pdoTools 2.x был массив `CoreTools::$timings` для профилирования
   - В 3.x он пустой — внутреннее профилирование отключено

2. **Fenom компилируется в PHP-код**:
   - Чанки и модификаторы превращаются в скомпилированный PHP
   - Отследить каждый вызов модификатора/чанка невозможно без модификации Fenom

3. **Парсер pdoTools обёрнут Modx3ProfilerToolbarParser**:
   - Modx3ProfilerToolbarParser успешно обёртывает pdoTools Parser
   - Но видит только верхнеуровневые вызовы (`processTag`)
   - Внутренняя работа Fenom остаётся скрытой

### Попытки решения

| Подход | Статус | Результат |
|--------|--------|-----------|
| Reflection для `CoreTools::$tags` | ❌ | Массив пустой в 3.x |
| Подмена `pdoTools\Parser` через Modx3ProfilerToolbarParser | ✅ | Работает — профилирует верхнеуровневые теги |
| Monkey-patching Fenom | ❌ | Рискованно — ломает кеш |
| Слушатель `debugParser()` | ❌ | Интерфейс не вызывается в 3.x |

## Рекомендации

### Для разработки

1. **Используйте обычные MODX-сниппеты** для тестирования профилировщика:
   ```php
   // core/components/mysite/elements/snippets/mysnippet.php
   <?php
   $iterations = !empty($iterations) ? (int)$iterations : 5;
   $output = '';
   for ($i = 1; $i <= $iterations; $i++) {
       $resource = $modx->getObject('modResource', $i);
       if ($resource) {
           $output .= $resource->get('pagetitle') . '<br>';
       }
       usleep(5000); // 5ms задержка
   }
   return $output;
   ```

2. **Смотрите SQL-запросы** — они профилируются отлично:
   - Top 5 Slowest SQL Queries
   - Queries time в Tags
   - N+1 Query Rule в Рекомендациях

3. **Мониторьте общее время pdoTools-сниппетов**:
   - Если `[[!msProducts]]` занимает 500 ms, проверьте:
     - Сколько SQL-запросов (N+1 problem)
     - Есть ли индексы
     - Размер tpl (слишком сложный Fenom)

### Для продакшена

1. **Кешируйте pdoTools-сниппеты** где возможно:
   ```modx
   [[msProducts?
       &parents=`0`
       &limit=`8`
       &tplCache=`1`  ← включить кеш tpl
   ]]
   ```

2. **Используйте `&includeContent=`0`` если контент не нужен**:
   ```modx
   [[!pdoResources?
       &parents=`0`
       &includeContent=`0`  ← экономит запросы
   ]]
   ```

3. **Оптимизируйте SQL-запросы на основе отчётов Modx3ProfilerToolbar**:
   - Смотрите Top 5 Slowest SQL Queries
   - Применяйте индексы
   - Используйте `&where` вместо `&where:=` (JSON медленнее)

## Примеры использования

### Нагрузочный шаблон (работающий)

```html
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>[[*pagetitle]] — Heavy Load Test</title>
</head>
<body>
    <h1>[[*pagetitle]]</h1>

    <p style="background:#fffbeb;padding:1rem;border-left:4px solid #f59e0b;margin-bottom:2rem;">
        <strong>⚠️ Важно:</strong> pdoTools сниппеты профилируются как верхнеуровневые теги.
        Для детальной диагностики используйте SQL-отчёты и Рекомендации.
    </p>

    <h2>🔍 Обычные MODX-сниппеты (детальное профилирование)</h2>
    <h3>1. Тест: 5 итераций</h3>
    [[!mprTestSnippet? &iterations=`5`]]

    <h3>2. Тест: 10 итераций</h3>
    [[!mprTestSnippet? &iterations=`10`]]

    <hr style="margin:2rem 0;">

    <h2>📦 pdoTools сниппеты (профилируются верхнеуровнево)</h2>
    <h3>3. msProducts: новинки</h3>
    [[!msProducts?
        &parents=`0`
        &limit=`8`
        &sortby=`id`
        &sortdir=`DESC`
        &tpl=`@INLINE <div style="padding:0.5rem;">{$pagetitle} — {$price} руб.</div>`
    ]]

    <h3>4. pdoPage: пагинация</h3>
    [[!pdoPage?
        &element=`msProducts`
        &parents=`0`
        &limit=`6`
        &tpl=`@INLINE <div>{$pagetitle}</div>`
    ]]
    <div>[[!+page.nav]]</div>
</body>
</html>
```

### Отчёт для этого шаблона

```
Modx3ProfilerToolbar Debug Report

Tags processed: 7 snippets
- [[!mprTestSnippet? &iterations=`5`]] — 45.67 ms, 5 SQL
- [[!mprTestSnippet? &iterations=`10`]] — 89.34 ms, 10 SQL
- [[!msProducts? &limit=`8` ...]] — 245.67 ms, 12 SQL
- [[!pdoPage? &element=`msProducts` ...]] — 189.45 ms, 8 SQL

Top 5 Slowest SQL Queries:
1. SELECT ... FROM modx_ms2_products ... — 18.45 ms
2. SELECT ... FROM modx_site_content ... — 12.34 ms
...

Recommendations:
⚠️ UncachedSnippetRule: 4 некешированных сниппета
💡 Suggestion: Кешируйте где возможно
📚 Docs: https://docs.modx.pro/components/pdotools/...
```

## Будущие улучшения

### Возможные решения (требуют изменений в pdoTools)

1. **Восстановить `CoreTools::$timings` в pdoTools 3.x**:
   - Форк pdoTools с профилированием
   - Pull request в оригинальный репозиторий

2. **Fenom middleware для профилирования**:
   - Создать middleware для `Fenom::compile()` с замером времени
   - Требует модификации Fenom

3. **Xdebug/Tideways профилирование**:
   - Использовать внешний профилировщик для детальной трассировки
   - Не интегрировано в Modx3ProfilerToolbar

### Текущее решение

**Modx3ProfilerToolbar профилирует что может:**
- ✅ Все обычные MODX-сниппеты (детально)
- ✅ pdoTools-сниппеты (верхний уровень)
- ✅ SQL-запросы (всё)
- ✅ MODX-события
- ✅ Рекомендации по производительности

**Для pdoTools-оптимизации:**
- Смотрите SQL-отчёты
- Проверяйте N+1 Query Rule
- Используйте кеш (`&tplCache=`1``)
- Оптимизируйте WHERE-условия

## Связанные документы

- [best-practices.md](./best-practices.md) — оптимизация pdoTools
- [faq.md](./faq.md) — частые вопросы
- [configuration.md](./configuration.md) — настройка профилировщика

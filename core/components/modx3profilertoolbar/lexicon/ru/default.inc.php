<?php
/**
 * Russian lexicon for Modx3ProfilerToolbar
 *
 * @package modx3profilertoolbar
 */

$_lang['modx3profilertoolbar'] = 'Профайлер производительности';
$_lang['modx3profilertoolbar_menu_desc'] = 'Анализ производительности MODX';
$_lang['mpr_menu_settings'] = 'Настройки';
$_lang['modx3profilertoolbar_error'] = 'Ошибка';
$_lang['modx3profilertoolbar_vuetools_required'] = 'Для работы админки профайлера требуется пакет VueTools.';

$_lang['mpr_dashboard_title'] = 'Профайлер производительности';
$_lang['mpr_dashboard'] = 'Дашборд';
$_lang['mpr_requests_title'] = 'Запросы';
$_lang['mpr_requests'] = 'Запросы';
$_lang['mpr_performance_score'] = 'Оценка производительности';
$_lang['mpr_top_bottlenecks'] = 'Узкие места';
$_lang['mpr_request_detail'] = 'Детали запроса';
$_lang['mpr_select_request'] = 'Выберите запрос из списка';
$_lang['mpr_timeline'] = 'Таймлайн';
$_lang['mpr_components'] = 'Компоненты';
$_lang['mpr_sql'] = 'SQL';
$_lang['mpr_tags'] = 'Теги';
$_lang['mpr_advisor'] = 'Рекомендации';
$_lang['mpr_filter_slow_sql'] = 'Медленные SQL';
$_lang['mpr_filter_uncached'] = 'Некэшированные сниппеты';
$_lang['mpr_btn_view'] = 'Просмотр';
$_lang['mpr_btn_delete'] = 'Удалить';
$_lang['mpr_btn_clear_old'] = 'Очистить старые трейсы';
$_lang['mpr_slowest_snippets'] = 'Самые медленные сниппеты';
$_lang['mpr_slowest_plugins'] = 'Самые медленные плагины';
$_lang['mpr_slowest_sql'] = 'Самые медленные SQL';
$_lang['mpr_summary'] = 'Сводка';
$_lang['mpr_avg_request'] = 'Среднее';
$_lang['mpr_p95'] = 'P95';
$_lang['mpr_sql_avg'] = 'SQL';
$_lang['mpr_no_requests'] = 'Нет запросов';
$_lang['mpr_no_data'] = 'Нет данных';
$_lang['mpr_toast_error'] = 'Ошибка запроса';

// Table columns
$_lang['mpr_col_name'] = 'Имя';
$_lang['mpr_col_time'] = 'Время';
$_lang['mpr_col_calls'] = 'Вызовов';
$_lang['mpr_col_query'] = 'Запрос';
$_lang['mpr_col_event'] = 'Событие';
$_lang['mpr_col_type'] = 'Тип';
$_lang['mpr_col_attempts'] = 'Попыток';
$_lang['mpr_col_queries'] = 'Запросов';
$_lang['mpr_col_memory'] = 'Память';
$_lang['mpr_label_others'] = 'Прочие';
$_lang['mpr_label_request_time'] = 'Время запроса (мс)';
$_lang['mpr_label_time_axis'] = 'Время';
$_lang['mpr_label_duration'] = 'Длительность (мс)';
$_lang['mpr_label_memory_mb'] = 'Память (MB)';
$_lang['mpr_label_parse_time'] = 'Время парсинга';
$_lang['mpr_label_query_time'] = 'Время запросов';
$_lang['mpr_empty_no_data'] = 'Нет данных';
$_lang['mpr_empty_no_sql'] = 'Нет SQL данных';
$_lang['mpr_empty_no_components'] = 'Нет компонентов';
$_lang['mpr_empty_no_tags'] = 'Нет тегов';
$_lang['mpr_empty_no_memory'] = 'Нет данных по памяти';
$_lang['mpr_empty_no_timeline'] = 'Нет данных таймлайна';
$_lang['mpr_label_total_time'] = 'Общее время:';
$_lang['mpr_label_ttfb'] = 'TTFB:';
$_lang['mpr_label_memory_peak'] = 'Пик памяти:';
$_lang['mpr_label_sql_count'] = 'Кол-во SQL:';
$_lang['mpr_performance_profiler'] = 'Профайлер производительности';
$_lang['mpr_yes'] = 'да';
$_lang['mpr_no'] = 'нет';
$_lang['mpr_debug_report_title'] = 'Отчёт отладки Modx3ProfilerToolbar';
$_lang['mpr_debug_report_desc'] = 'Обработанные теги (сортировка по времени парсинга по убыванию).';
$_lang['mpr_debug_col_number'] = '#';
$_lang['mpr_debug_col_tag'] = 'Тег';
$_lang['mpr_debug_col_type'] = 'Тип';
$_lang['mpr_debug_col_attempts'] = 'Попыток';
$_lang['mpr_debug_col_queries'] = 'Запросов';
$_lang['mpr_debug_col_queries_time'] = 'Время запросов';
$_lang['mpr_debug_col_parse_time'] = 'Время парсинга';
$_lang['mpr_debug_footer'] = 'общее время парсинга: %s с | время запросов: %s с | запросов: %s | пик памяти: %s MB | PHP: %s | из кэша: %s';

// Charts
$_lang['mpr_chart_memory'] = 'Использование памяти';
$_lang['mpr_chart_trend'] = 'Тренд запросов';
$_lang['mpr_chart_tags_breakdown'] = 'Время парсинга тегов';
$_lang['mpr_chart_components_heatmap'] = 'Производительность компонентов';
$_lang['mpr_chart_sql_distribution'] = 'Распределение времени SQL';
$_lang['mpr_tab_memory'] = 'Память';
$_lang['mpr_tab_overview'] = 'Обзор';
$_lang['mpr_timeline_chart'] = 'Визуализация временной шкалы';
$_lang['mpr_timeline_table'] = 'События';
$_lang['mpr_components_heatmap'] = 'Тепловая карта компонентов';
$_lang['mpr_sql_distribution'] = 'Распределение SQL-запросов';
$_lang['mpr_sql_queries'] = 'SQL запросы';

// Toolbar
$_lang['mpr_slow_components'] = 'Медленные компоненты';
$_lang['mpr_total_time'] = 'Общее время';
$_lang['mpr_ttfb'] = 'TTFB';
$_lang['mpr_php'] = 'PHP';
$_lang['mpr_sql_time'] = 'SQL';
$_lang['mpr_memory'] = 'Память';
$_lang['mpr_snippets'] = 'сниппетов';
$_lang['mpr_sql_queries'] = 'SQL';
$_lang['mpr_query_time'] = 'Время запросов';
$_lang['mpr_source'] = 'Источник';
$_lang['mpr_open_trace'] = 'Открыть полный трейс в админке';
$_lang['mpr_time'] = 'Время';
$_lang['mpr_calls'] = 'Вызовов';
$_lang['mpr_cached'] = 'кэш';
$_lang['mpr_possible_n1'] = 'Возможная проблема N+1';

// Advisor rules
$_lang['mpr_advisor_uncached_snippet'] = 'Медленные некэшированные сниппеты: %s';
$_lang['mpr_advisor_uncached_suggestion'] = 'Включите кэширование сниппетов или оптимизируйте запросы';
$_lang['mpr_advisor_n1_query'] = 'Обнаружены N+1 запросы';
$_lang['mpr_advisor_n1_suggestion'] = 'Используйте JOIN вместо запросов в цикле';
$_lang['mpr_advisor_slow_query'] = 'Обнаружены медленные запросы (%d)';
$_lang['mpr_advisor_slow_query_suggestion'] = 'Добавьте индексы или оптимизируйте запросы';
$_lang['mpr_advisor_chunk_overuse'] = 'Чанки с большим количеством вызовов: %s';
$_lang['mpr_advisor_chunk_overuse_suggestion'] = 'Используйте кэш Fenom или уменьшите размер цикла';
$_lang['mpr_advisor_too_many_snippets'] = 'Слишком много сниппетов на странице (%d сниппетов)';
$_lang['mpr_advisor_too_many_snippets_suggestion'] = 'Используйте кэширование или объедините сниппеты';
$_lang['mpr_advisor_too_many_snippets_info'] = 'На странице %d сниппетов';
$_lang['mpr_advisor_too_many_snippets_info_suggestion'] = 'Следите за производительностью при увеличении количества';

// Debug report
$_lang['mpr_debug_stats_title'] = 'Статистика компонентов';
$_lang['mpr_debug_snippets'] = 'Сниппеты';
$_lang['mpr_debug_plugins'] = 'Плагины';
$_lang['mpr_debug_chunks'] = 'Чанки';
$_lang['mpr_debug_slowest'] = 'Самый медленный';
$_lang['mpr_debug_total_time'] = 'общее время';
$_lang['mpr_debug_events_title'] = 'MODX События';
$_lang['mpr_debug_event'] = 'Событие';
$_lang['mpr_debug_duration'] = 'Длительность';
$_lang['mpr_debug_sql_title'] = 'Топ 5 медленных SQL запросов';
$_lang['mpr_debug_query'] = 'Запрос';
$_lang['mpr_debug_snippets_count_info'] = 'На странице %d сниппетов. Следите за производительностью при увеличении количества.';
$_lang['mpr_debug_snippets_count_warning'] = 'Внимание: Слишком много сниппетов на странице (%d сниппетов). Используйте кэширование или объедините сниппеты.';

// Settings
$_lang['setting_modx3profilertoolbar.enabled'] = 'Включить профайлер';
$_lang['setting_modx3profilertoolbar.enabled_desc'] = 'Включить профилирование запросов.';
$_lang['setting_modx3profilertoolbar.sampling_rate'] = 'Частота выборки (%)';
$_lang['setting_modx3profilertoolbar.sampling_rate_desc'] = 'Процент запросов для профилирования (1–100).';
$_lang['setting_modx3profilertoolbar.frontend_toolbar'] = 'Тулбар на фронте';
$_lang['setting_modx3profilertoolbar.frontend_toolbar_desc'] = 'Показывать панель производительности на фронте (контекст web) авторизованным в менеджере.';
$_lang['setting_modx3profilertoolbar.retention_days'] = 'Хранение (дней)';
$_lang['setting_modx3profilertoolbar.retention_days_desc'] = 'Удалять трейсы старше указанного количества дней.';
$_lang['setting_modx3profilertoolbar.slow_query_ms'] = 'Порог медленного запроса (мс)';
$_lang['setting_modx3profilertoolbar.slow_query_ms_desc'] = 'Запросы медленнее этого считаются медленными.';
$_lang['setting_modx3profilertoolbar.slow_snippet_ms'] = 'Порог медленного сниппета (мс)';
$_lang['setting_modx3profilertoolbar.slow_snippet_ms_desc'] = 'Сниппеты медленнее этого помечаются.';
$_lang['setting_modx3profilertoolbar.cli_enabled'] = 'Профилировать CLI';
$_lang['setting_modx3profilertoolbar.cli_enabled_desc'] = 'Профилировать CLI-запросы.';
$_lang['setting_modx3profilertoolbar.storage_path'] = 'Путь к трейсам';
$_lang['setting_modx3profilertoolbar.storage_path_desc'] = 'Каталог для raw-файлов трейсов. Пусто = core/cache/modx3profilertoolbar/traces/';

// Cleanup
$_lang['mpr_btn_cleanup'] = 'Очистить старые трейсы';
$_lang['mpr_btn_cancel'] = 'Отмена';
$_lang['mpr_cleanup_title'] = 'Очистка трейсов';
$_lang['mpr_cleanup_confirm'] = 'Удалить трейсы старше {days} дней?';
$_lang['mpr_cleanup_success'] = 'Очистка завершена';
$_lang['mpr_cleanup_error'] = 'Ошибка очистки';
$_lang['mpr_cleanup_result'] = 'Удалено {db} записей БД, {files} файлов';
$_lang['mpr_cleanup_aria'] = 'Удалить старые данные профилирования';

// Performance score messages
$_lang['mpr_score_good'] = 'Время рендеринга в допустимых пределах.';
$_lang['mpr_score_needs_optimization'] = 'Рекомендуется оптимизировать медленные компоненты и SQL.';
$_lang['mpr_status_excellent'] = 'Отлично';
$_lang['mpr_status_fair'] = 'Удовлетворительно';
$_lang['mpr_status_critical'] = 'Критично';
$_lang['mpr_status_critical_desc'] = 'Обнаружены серьёзные проблемы производительности.';

// Tooltips (подсказки для метрик)
$_lang['mpr_tooltip_avg'] = 'Среднее арифметическое время выполнения всех запросов.';
$_lang['mpr_tooltip_p95'] = '95-й перцентиль — 95% запросов выполняются быстрее этого значения.';
$_lang['mpr_tooltip_sql'] = 'Общее время выполнения SQL-запросов.';
$_lang['mpr_tooltip_ttfb'] = 'Time To First Byte — время до первого байта ответа сервера.';
$_lang['mpr_tooltip_total_time'] = 'Общее время выполнения запроса в миллисекундах.';
$_lang['mpr_tooltip_memory_peak'] = 'Пиковое потребление памяти за время выполнения запроса.';
$_lang['mpr_tooltip_sql_count'] = 'Количество выполненных SQL-запросов.';

// Accessibility
$_lang['mpr_aria_requests_table'] = 'Таблица последних запросов профилирования';

// Debug SQL note
$_lang['mpr_debug_sql_note'] = 'Показаны сниппеты и плагины с наибольшим временем выполнения SQL-запросов.';

// Documentation links titles
$_lang['mpr_doc_pdotools_general'] = 'pdoTools: Общие параметры (оптимизация запросов)';
$_lang['mpr_doc_pdotools_parser'] = 'pdoTools: Парсер (Fenom для быстрых чанков)';
$_lang['mpr_doc_pdoresources'] = 'pdoResources: select, join, fastMode';
$_lang['mpr_doc_fenom_docs'] = 'Fenom: Документация по шаблонизатору';
$_lang['mpr_doc_modx_caching'] = 'MODX: Кэширование элементов';
$_lang['mpr_doc_pdotools_replace_snippets'] = 'pdoTools: Замена множества сниппетов на pdoResources';
$_lang['mpr_doc_fenom_in_chunks'] = 'Fenom: Использование в чанках вместо сниппетов';
$_lang['mpr_doc_pdotools_fastmode'] = 'pdoTools: fastMode для чанков';
$_lang['mpr_doc_pdotools_chunk_types'] = 'pdoTools: Парсер и типы чанков (@INLINE, @FILE)';
$_lang['mpr_doc_fenom_syntax'] = 'Fenom: Синтаксис и возможности';
$_lang['mpr_doc_fenom_modifiers'] = 'Fenom: Модификаторы для оптимизации';
$_lang['mpr_doc_pdotools_joins'] = 'pdoTools: leftJoin, rightJoin, innerJoin';
$_lang['mpr_doc_pdoresources_optimization'] = 'pdoResources: Оптимизация выборок';
$_lang['mpr_doc_pdotools_select'] = 'pdoTools: select для выборки нужных полей';
$_lang['mpr_doc_fenom_single_query'] = 'Fenom: Единый запрос через $_modx';
$_lang['mpr_doc_pdotools_cache'] = 'pdoTools: Кэширование результатов (cacheTime, cache_key)';
$_lang['mpr_doc_fenom_cache_control'] = 'Fenom: Управление кэшированием';
$_lang['mpr_doc_pdotools_performance'] = 'pdoTools: Оптимизация производительности';


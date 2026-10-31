<?php
/**
 * English lexicon for Modx3ProfilerToolbar
 *
 * @package modx3profilertoolbar
 */

$_lang['modx3profilertoolbar'] = 'Performance Profiler';
$_lang['modx3profilertoolbar_menu_desc'] = 'Performance analysis for MODX';
$_lang['mpr_menu_settings'] = 'Settings';
$_lang['modx3profilertoolbar_error'] = 'Error';
$_lang['modx3profilertoolbar_vuetools_required'] = 'VueTools package is required for the Performance Profiler admin UI.';

$_lang['mpr_dashboard_title'] = 'Performance Profiler';
$_lang['mpr_dashboard'] = 'Dashboard';
$_lang['mpr_requests_title'] = 'Requests';
$_lang['mpr_requests'] = 'Requests';
$_lang['mpr_performance_score'] = 'Performance Score';
$_lang['mpr_top_bottlenecks'] = 'Top Bottlenecks';
$_lang['mpr_request_detail'] = 'Request detail';
$_lang['mpr_select_request'] = 'Select a request from the list';
$_lang['mpr_timeline'] = 'Timeline';
$_lang['mpr_components'] = 'Components';
$_lang['mpr_sql'] = 'SQL';
$_lang['mpr_tags'] = 'Tags';
$_lang['mpr_advisor'] = 'Advisor';
$_lang['mpr_filter_slow_sql'] = 'Slow SQL';
$_lang['mpr_filter_uncached'] = 'Uncached snippets';
$_lang['mpr_btn_view'] = 'View';
$_lang['mpr_btn_delete'] = 'Delete';
$_lang['mpr_btn_clear_old'] = 'Clear old traces';
$_lang['mpr_slowest_snippets'] = 'Slowest Snippets';
$_lang['mpr_slowest_plugins'] = 'Slowest Plugins';
$_lang['mpr_slowest_sql'] = 'Slowest SQL';
$_lang['mpr_summary'] = 'Summary';
$_lang['mpr_avg_request'] = 'Average';
$_lang['mpr_p95'] = 'P95';
$_lang['mpr_sql_avg'] = 'SQL';
$_lang['mpr_no_requests'] = 'No requests';
$_lang['mpr_no_data'] = 'No data';
$_lang['mpr_toast_error'] = 'Request failed';

// Table columns
$_lang['mpr_col_name'] = 'Name';
$_lang['mpr_col_time'] = 'Time';
$_lang['mpr_col_calls'] = 'Calls';
$_lang['mpr_col_query'] = 'Query';
$_lang['mpr_col_event'] = 'Event';
$_lang['mpr_col_type'] = 'Type';
$_lang['mpr_col_attempts'] = 'Attempts';
$_lang['mpr_col_queries'] = 'Queries';
$_lang['mpr_col_memory'] = 'Memory';
$_lang['mpr_label_others'] = 'Others';
$_lang['mpr_label_request_time'] = 'Request Time (ms)';
$_lang['mpr_label_time_axis'] = 'Time';
$_lang['mpr_label_duration'] = 'Duration (ms)';
$_lang['mpr_label_memory_mb'] = 'Memory (MB)';
$_lang['mpr_label_parse_time'] = 'Parse Time';
$_lang['mpr_label_query_time'] = 'Query Time';
$_lang['mpr_empty_no_data'] = 'No data available';
$_lang['mpr_empty_no_sql'] = 'No SQL data';
$_lang['mpr_empty_no_components'] = 'No components data';
$_lang['mpr_empty_no_tags'] = 'No tags data';
$_lang['mpr_empty_no_memory'] = 'No memory data';
$_lang['mpr_empty_no_timeline'] = 'No timeline data';
$_lang['mpr_label_total_time'] = 'Total time:';
$_lang['mpr_label_ttfb'] = 'TTFB:';
$_lang['mpr_label_memory_peak'] = 'Memory peak:';
$_lang['mpr_label_sql_count'] = 'SQL count:';
$_lang['mpr_performance_profiler'] = 'Performance Profiler';
$_lang['mpr_yes'] = 'yes';
$_lang['mpr_no'] = 'no';
$_lang['mpr_debug_report_title'] = 'Modx3ProfilerToolbar Debug Report';
$_lang['mpr_debug_report_desc'] = 'Tags processed (sorted by parse time descending).';
$_lang['mpr_debug_col_number'] = '#';
$_lang['mpr_debug_col_tag'] = 'Tag';
$_lang['mpr_debug_col_type'] = 'Type';
$_lang['mpr_debug_col_attempts'] = 'Attempts';
$_lang['mpr_debug_col_queries'] = 'Queries';
$_lang['mpr_debug_col_queries_time'] = 'Queries time';
$_lang['mpr_debug_col_parse_time'] = 'Parse time';
$_lang['mpr_debug_footer'] = 'total parse time: %s s | query time: %s s | queries: %s | memory peak: %s MB | PHP: %s | from_cache: %s';

// Charts
$_lang['mpr_chart_memory'] = 'Memory Usage';
$_lang['mpr_chart_trend'] = 'Requests Trend';
$_lang['mpr_chart_tags_breakdown'] = 'Tags Parse Time';
$_lang['mpr_chart_components_heatmap'] = 'Components Performance';
$_lang['mpr_chart_sql_distribution'] = 'SQL Time Distribution';
$_lang['mpr_tab_memory'] = 'Memory';
$_lang['mpr_tab_overview'] = 'Overview';
$_lang['mpr_timeline_chart'] = 'Timeline Visualization';
$_lang['mpr_timeline_table'] = 'Events';
$_lang['mpr_components_heatmap'] = 'Components Heatmap';
$_lang['mpr_sql_distribution'] = 'SQL Distribution';
$_lang['mpr_sql_queries'] = 'SQL Queries';

// Toolbar
$_lang['mpr_slow_components'] = 'Slow Components';
$_lang['mpr_total_time'] = 'Total time';
$_lang['mpr_ttfb'] = 'TTFB';
$_lang['mpr_php'] = 'PHP';
$_lang['mpr_sql_time'] = 'SQL';
$_lang['mpr_memory'] = 'Memory';
$_lang['mpr_snippets'] = 'snippets';
$_lang['mpr_sql_queries'] = 'SQL';
$_lang['mpr_query_time'] = 'Query time';
$_lang['mpr_source'] = 'Source';
$_lang['mpr_open_trace'] = 'Open full trace in Manager';
$_lang['mpr_time'] = 'Time';
$_lang['mpr_calls'] = 'Calls';
$_lang['mpr_cached'] = 'cached';
$_lang['mpr_possible_n1'] = 'Possible N+1';

// Advisor rules
$_lang['mpr_advisor_uncached_snippet'] = 'Slow uncached snippets: %s';
$_lang['mpr_advisor_uncached_suggestion'] = 'Enable snippet caching or optimize queries';
$_lang['mpr_advisor_n1_query'] = 'N+1 queries detected';
$_lang['mpr_advisor_n1_suggestion'] = 'Use JOIN instead of queries in loop';
$_lang['mpr_advisor_slow_query'] = 'Slow queries detected (%d)';
$_lang['mpr_advisor_slow_query_suggestion'] = 'Add indexes or optimize queries';
$_lang['mpr_advisor_chunk_overuse'] = 'Chunks with many calls: %s';
$_lang['mpr_advisor_chunk_overuse_suggestion'] = 'Use Fenom cache or reduce loop size';
$_lang['mpr_advisor_too_many_snippets'] = 'Too many snippets on this page (%d snippets)';
$_lang['mpr_advisor_too_many_snippets_suggestion'] = 'Consider caching snippets or combining them into fewer calls';
$_lang['mpr_advisor_too_many_snippets_info'] = 'Page has %d snippets';
$_lang['mpr_advisor_too_many_snippets_info_suggestion'] = 'Monitor performance as snippet count increases';

// Debug report
$_lang['mpr_debug_stats_title'] = 'Component Statistics';
$_lang['mpr_debug_snippets'] = 'Snippets';
$_lang['mpr_debug_plugins'] = 'Plugins';
$_lang['mpr_debug_chunks'] = 'Chunks';
$_lang['mpr_debug_slowest'] = 'Slowest';
$_lang['mpr_debug_total_time'] = 'total time';
$_lang['mpr_debug_events_title'] = 'MODX Events';
$_lang['mpr_debug_event'] = 'Event';
$_lang['mpr_debug_duration'] = 'Duration';
$_lang['mpr_debug_sql_title'] = 'Top 5 Slowest SQL Queries';
$_lang['mpr_debug_query'] = 'Query';
$_lang['mpr_debug_snippets_count_info'] = 'Page has %d snippets. Monitor performance as count increases.';
$_lang['mpr_debug_snippets_count_warning'] = 'Warning: Too many snippets on this page (%d snippets). Consider caching or combining them.';

// Settings
$_lang['setting_modx3profilertoolbar.enabled'] = 'Enable profiler';
$_lang['setting_modx3profilertoolbar.enabled_desc'] = 'Enable request profiling.';
$_lang['setting_modx3profilertoolbar.sampling_rate'] = 'Sampling rate (%)';
$_lang['setting_modx3profilertoolbar.sampling_rate_desc'] = 'Percentage of requests to profile (1–100).';
$_lang['setting_modx3profilertoolbar.frontend_toolbar'] = 'Frontend toolbar';
$_lang['setting_modx3profilertoolbar.frontend_toolbar_desc'] = 'Show performance toolbar on frontend (web context) for manager users.';
$_lang['setting_modx3profilertoolbar.retention_days'] = 'Retention (days)';
$_lang['setting_modx3profilertoolbar.retention_days_desc'] = 'Auto-delete traces older than this many days.';
$_lang['setting_modx3profilertoolbar.slow_query_ms'] = 'Slow query threshold (ms)';
$_lang['setting_modx3profilertoolbar.slow_query_ms_desc'] = 'Queries slower than this are flagged as slow.';
$_lang['setting_modx3profilertoolbar.slow_snippet_ms'] = 'Slow snippet threshold (ms)';
$_lang['setting_modx3profilertoolbar.slow_snippet_ms_desc'] = 'Snippets slower than this are flagged.';
$_lang['setting_modx3profilertoolbar.cli_enabled'] = 'Profile CLI';
$_lang['setting_modx3profilertoolbar.cli_enabled_desc'] = 'Profile CLI requests.';
$_lang['setting_modx3profilertoolbar.cli_output'] = 'CLI Output';
$_lang['setting_modx3profilertoolbar.cli_output_desc'] = 'Where to output profiling results in CLI: console (to stdout) or file (to a file).';
$_lang['setting_modx3profilertoolbar.cli_output_file'] = 'CLI Output File';
$_lang['setting_modx3profilertoolbar.cli_output_file_desc'] = 'Path to file for saving traces in CLI mode. Only used if cli_output = file.';
$_lang['setting_modx3profilertoolbar.cli_output_format'] = 'CLI Output Format';
$_lang['setting_modx3profilertoolbar.cli_output_format_desc'] = 'Output format for CLI profiling results: json, text, or csv.';
$_lang['setting_modx3profilertoolbar.storage_path'] = 'Trace storage path';
$_lang['setting_modx3profilertoolbar.storage_path_desc'] = 'Directory for raw trace files. Empty = core/cache/modx3profilertoolbar/traces/';

// Cleanup
$_lang['mpr_btn_cleanup'] = 'Cleanup Old Traces';
$_lang['mpr_btn_cancel'] = 'Cancel';
$_lang['mpr_cleanup_title'] = 'Cleanup Traces';
$_lang['mpr_cleanup_confirm'] = 'Delete traces older than {days} days?';
$_lang['mpr_cleanup_success'] = 'Cleanup completed';
$_lang['mpr_cleanup_error'] = 'Cleanup failed';
$_lang['mpr_cleanup_result'] = 'Removed {db} DB records, {files} files';
$_lang['mpr_cleanup_aria'] = 'Delete old profiling data';

// Performance score messages
$_lang['mpr_score_good'] = 'Rendering time is within acceptable range.';
$_lang['mpr_score_needs_optimization'] = 'Consider optimizing slow components and SQL.';
$_lang['mpr_status_excellent'] = 'Excellent';
$_lang['mpr_status_fair'] = 'Fair';
$_lang['mpr_status_critical'] = 'Critical';
$_lang['mpr_status_critical_desc'] = 'Serious performance issues detected.';

// Tooltips
$_lang['mpr_tooltip_avg'] = 'Average execution time across all requests.';
$_lang['mpr_tooltip_p95'] = '95th percentile — 95% of requests complete faster than this.';
$_lang['mpr_tooltip_sql'] = 'Total time spent on SQL queries.';
$_lang['mpr_tooltip_ttfb'] = 'Time To First Byte — time until first byte of server response.';
$_lang['mpr_tooltip_total_time'] = 'Total request execution time in milliseconds.';
$_lang['mpr_tooltip_memory_peak'] = 'Peak memory usage during request execution.';
$_lang['mpr_tooltip_sql_count'] = 'Number of SQL queries executed.';

// Accessibility
$_lang['mpr_aria_requests_table'] = 'Profiling requests table';

// Debug SQL note
$_lang['mpr_debug_sql_note'] = 'Snippets and plugins with the highest SQL execution time are shown.';

// Documentation links titles
$_lang['mpr_doc_pdotools_general'] = 'pdoTools: General Properties (query optimization)';
$_lang['mpr_doc_pdotools_parser'] = 'pdoTools: Parser (Fenom for fast chunks)';
$_lang['mpr_doc_pdoresources'] = 'pdoResources: select, join, fastMode';
$_lang['mpr_doc_fenom_docs'] = 'Fenom: Template Engine Documentation';
$_lang['mpr_doc_modx_caching'] = 'MODX: Element Caching';
$_lang['mpr_doc_pdotools_replace_snippets'] = 'pdoTools: Replace Multiple Snippets with pdoResources';
$_lang['mpr_doc_fenom_in_chunks'] = 'Fenom: Use in Chunks Instead of Snippets';
$_lang['mpr_doc_pdotools_fastmode'] = 'pdoTools: fastMode for Chunks';
$_lang['mpr_doc_pdotools_chunk_types'] = 'pdoTools: Parser and Chunk Types (@INLINE, @FILE)';
$_lang['mpr_doc_fenom_syntax'] = 'Fenom: Syntax and Capabilities';
$_lang['mpr_doc_fenom_modifiers'] = 'Fenom: Modifiers for Optimization';
$_lang['mpr_doc_pdotools_joins'] = 'pdoTools: leftJoin, rightJoin, innerJoin';
$_lang['mpr_doc_pdoresources_optimization'] = 'pdoResources: Query Optimization';
$_lang['mpr_doc_pdotools_select'] = 'pdoTools: select for Required Fields';
$_lang['mpr_doc_fenom_single_query'] = 'Fenom: Single Query via $_modx';
$_lang['mpr_doc_pdotools_cache'] = 'pdoTools: Result Caching (cacheTime, cache_key)';
$_lang['mpr_doc_fenom_cache_control'] = 'Fenom: Cache Control';
$_lang['mpr_doc_pdotools_performance'] = 'pdoTools: Performance Optimization';


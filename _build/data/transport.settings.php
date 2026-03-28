<?php
/**
 * Системные настройки Modx3ProfilerToolbar
 *
 * @package modx3profilertoolbar
 * @return array
 */

return [
    'enabled' => [
        'value' => '0',
        'xtype' => 'combo-boolean',
        'name' => 'setting_modx3profilertoolbar.enabled',
        'description' => 'setting_modx3profilertoolbar.enabled_desc',
    ],
    'sampling_rate' => [
        'value' => '100',
        'xtype' => 'numberfield',
        'name' => 'setting_modx3profilertoolbar.sampling_rate',
        'description' => 'setting_modx3profilertoolbar.sampling_rate_desc',
    ],
    'frontend_toolbar' => [
        'value' => '1',
        'xtype' => 'combo-boolean',
        'name' => 'setting_modx3profilertoolbar.frontend_toolbar',
        'description' => 'setting_modx3profilertoolbar.frontend_toolbar_desc',
    ],
    'slow_query_ms' => [
        'value' => '100',
        'xtype' => 'numberfield',
        'name' => 'setting_modx3profilertoolbar.slow_query_ms',
        'description' => 'setting_modx3profilertoolbar.slow_query_ms_desc',
    ],
    'slow_snippet_ms' => [
        'value' => '50',
        'xtype' => 'numberfield',
        'name' => 'setting_modx3profilertoolbar.slow_snippet_ms',
        'description' => 'setting_modx3profilertoolbar.slow_snippet_ms_desc',
    ],
    'cli_enabled' => [
        'value' => '0',
        'xtype' => 'combo-boolean',
        'name' => 'setting_modx3profilertoolbar.cli_enabled',
        'description' => 'setting_modx3profilertoolbar.cli_enabled_desc',
    ],
];

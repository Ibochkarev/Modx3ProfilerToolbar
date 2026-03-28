<?php
/**
 * Plugin Modx3ProfilerToolbar Transport
 *
 * @package modx3profilertoolbar
 * @return array
 */

return [
    'name' => 'Modx3ProfilerToolbar',
    'description' => 'Performance toolbar for MODX 3 (frontend only)',
    'file' => 'plugin.modx3profilertoolbar',
    'events' => [
        'OnMODXInit' => ['priority' => -100], // Выполняется ДО pdoTools и MiniShop3
        'OnHandleRequest' => ['priority' => 0],
        'OnLoadWebDocument' => ['priority' => 0],
        'OnWebPagePrerender' => ['priority' => 0],
    ],
];

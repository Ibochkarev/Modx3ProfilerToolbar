<?php
/**
 * Modx3ProfilerToolbar build configuration
 *
 * @package modx3profilertoolbar
 */

// MODX paths (auto-detect or override)
if (!defined('MODX_CORE_PATH')) {
    $dir = __DIR__;
    // Try to find MODX root (assumes Extras/ModProfiler structure)
    while ($dir && !file_exists($dir . '/config.core.php')) {
        $parent = dirname($dir);
        if ($parent === $dir) break;
        $dir = $parent;
    }
    if (file_exists($dir . '/config.core.php')) {
        require_once $dir . '/config.core.php';
    } else {
        die('Could not find MODX config.core.php');
    }
}

return [
    'name' => 'Modx3ProfilerToolbar',
    'name_lower' => 'modx3profilertoolbar',
    'version' => '1.0.1',
    'release' => 'pl',
    'install' => false, // Auto-install after build
    'encrypt' => false, // Free version without encryption
    'update' => [
        'settings' => false,
        'plugins' => true,
        'menus' => true,
    ],
];

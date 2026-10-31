<?php
/**
 * Интеграционный тест CLI вывода для Modx3ProfilerToolbar
 * 
 * Запуск из корня MODX:
 *   php Extras/Modx3ProfilerToolbar/tests/test_cli_integration.php
 */

// Определяем корень MODX
$modxRoot = dirname(__DIR__, 3);
define('MODX_BASE_PATH', $modxRoot . '/');
define('MODX_CORE_PATH', $modxRoot . '/core/');
define('MODX_ASSETS_PATH', $modxRoot . '/assets/');
define('MODX_CONFIG_KEY', 'config');
define('MODX_BASE_URL', '/');

// Подключаем конфиг
require_once MODX_CORE_PATH . 'config/config.inc.php';

// Подключаем MODX через config
require_once MODX_CORE_PATH . 'include/deprecated.php';

// Инициализируем MODX
require_once MODX_CORE_PATH . 'vendor/autoload.php';
$modx = new modX();
$modx->initialize('mgr');

// Проверяем, что MODX загрузился
if (!($modx instanceof modX)) {
    echo "ERROR: MODX не загрузился\n";
    exit(1);
}

echo "=== Modx3ProfilerToolbar CLI Integration Test ===\n\n";
echo "MODX Version: " . $modx->getVersion() . "\n";
echo "PHP SAPI: " . php_sapi_name() . "\n\n";

// Проверяем наличие системных настроек
$settings = [
    'modx3profilertoolbar.enabled',
    'modx3profilertoolbar.cli_enabled',
    'modx3profilertoolbar.cli_output',
    'modx3profilertoolbar.cli_output_file',
    'modx3profilertoolbar.cli_output_format',
];

echo "--- System Settings Check ---\n";
foreach ($settings as $key) {
    $setting = $modx->getObject('modSystemSetting', ['key' => $key]);
    if ($setting) {
        echo "✓ $key = " . $setting->get('value') . "\n";
    } else {
        echo "✗ $key NOT FOUND (needs install/rebuild)\n";
    }
}

echo "\n--- ProfilerService Class Check ---\n";
if (class_exists('Modx3ProfilerToolbar\Service\ProfilerService')) {
    echo "✓ ProfilerService class loaded\n";
    
    // Проверяем наличие новых методов через Reflection
    $reflection = new ReflectionClass('Modx3ProfilerToolbar\Service\ProfilerService');
    
    $methods = [
        'outputCliTrace',
        'formatTraceAsText',
        'formatTraceAsCsv',
        'formatBytes',
        'escapeCsvField',
    ];
    
    foreach ($methods as $methodName) {
        if ($reflection->hasMethod($methodName)) {
            echo "✓ Method $methodName exists\n";
        } else {
            echo "✗ Method $methodName MISSING\n";
        }
    }
    
    // Проверяем свойства
    $properties = [
        'cliOutput',
        'cliOutputFile',
        'cliOutputFormat',
    ];
    
    echo "\n--- Class Properties Check ---\n";
    foreach ($properties as $propName) {
        if ($reflection->hasProperty($propName)) {
            echo "✓ Property $propName exists\n";
        } else {
            echo "✗ Property $propName MISSING\n";
        }
    }
} else {
    echo "✗ ProfilerService class NOT found\n";
    echo "Check autoloading or component installation\n";
    exit(1);
}

echo "\n--- Test: Create ProfilerService Instance ---\n";
try {
    $profiler = new \Modx3ProfilerToolbar\Service\ProfilerService($modx);
    echo "✓ ProfilerService instantiated successfully\n";
    
    // Проверяем, что настройки читаются
    $reflection = new ReflectionClass($profiler);
    $cliOutputProp = $reflection->getProperty('cliOutput');
    $cliOutputProp->setAccessible(true);
    echo "✓ cliOutput = " . $cliOutputProp->getValue($profiler) . "\n";
    
    $cliOutputFormatProp = $reflection->getProperty('cliOutputFormat');
    $cliOutputFormatProp->setAccessible(true);
    echo "✓ cliOutputFormat = " . $cliOutputFormatProp->getValue($profiler) . "\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n--- Test: Helper Methods ---\n";

// Тест formatBytes
$method = $reflection->getMethod('formatBytes');
$method->setAccessible(true);

$bytesTests = [
    1024 => '1 KB',
    1048576 => '1 MB',
    500 => '500 B',
];

foreach ($bytesTests as $input => $expected) {
    $result = $method->invoke($profiler, $input);
    if ($result === $expected) {
        echo "✓ formatBytes($input) = $result\n";
    } else {
        echo "✗ formatBytes($input) = $result (expected: $expected)\n";
    }
}

// Тест escapeCsvField
$method = $reflection->getMethod('escapeCsvField');
$method->setAccessible(true);

$csvTests = [
    'Simple' => 'Simple',
    'Hello, World' => '"Hello, World"',
    'Say "Hi"' => '"Say ""Hi"""',
];

foreach ($csvTests as $input => $expected) {
    $result = $method->invoke($profiler, $input);
    if ($result === $expected) {
        echo "✓ escapeCsvField('$input') = $result\n";
    } else {
        echo "✗ escapeCsvField('$input') = $result (expected: $expected)\n";
    }
}

echo "\n--- Test: formatTraceAsText (sample output) ---\n";
$method = $reflection->getMethod('formatTraceAsText');
$method->setAccessible(true);

$sampleTrace = [
    'request_id' => 'test_' . time(),
    'context' => 'cli',
    'url' => '/cli/test',
    'method' => 'CLI',
    'metrics' => [
        'total_time' => 0.125,
        'php_time' => 0.1,
        'sql_time' => 0.025,
        'memory_peak' => 8388608,
    ],
    'sql_count' => 1,
    'snippet_count' => 2,
    'chunk_count' => 1,
    'performance_score' => 85,
    'advisor' => ['message' => 'Performance is good'],
    'events' => [
        ['name' => 'OnMODXInit', 'duration' => 0.05, 'type' => 'event'],
        ['name' => 'TestSnippet', 'duration' => 0.03, 'type' => 'snippet', 'cached' => false],
    ],
    'sql' => [
        ['query' => 'SELECT * FROM modx_site_content WHERE id = 1', 'time' => 0.015],
    ],
];

$textOutput = $method->invoke($profiler, $sampleTrace);
echo "Sample text output (first 200 chars):\n";
echo substr($textOutput, 0, 200) . "...\n";

if (strpos($textOutput, '--- Modx3ProfilerToolbar CLI Trace ---') !== false) {
    echo "✓ Text format contains header\n";
}
if (strpos($textOutput, 'Performance Score: 85') !== false) {
    echo "✓ Text format contains performance score\n";
}
if (strpos($textOutput, '8 MB') !== false) {
    echo "✓ Text format contains formatted memory\n";
}

echo "\n--- Test: formatTraceAsCsv (sample output) ---\n";
$method = $reflection->getMethod('formatTraceAsCsv');
$method->setAccessible(true);

$csvOutput = $method->invoke($profiler, $sampleTrace);
echo "Sample CSV output:\n";
echo $csvOutput . "\n";

if (strpos($csvOutput, 'Name,Type,Duration(ms),Cached') === 0) {
    echo "✓ CSV format has correct header\n";
}

echo "\n=== All Tests Completed ===\n";
exit(0);

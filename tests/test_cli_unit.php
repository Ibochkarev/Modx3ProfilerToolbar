<?php
/**
 * Unit тест CLI функциональности Modx3ProfilerToolbar
 * 
 * Запуск:
 *   php Extras/Modx3ProfilerToolbar/tests/test_cli_unit.php
 */

echo "=== Modx3ProfilerToolbar CLI Unit Tests ===\n\n";

// Autoload для классов
spl_autoload_register(function ($class) {
    $base = __DIR__ . '/../core/components/modx3profilertoolbar/src/';
    $file = $base . str_replace('\\', '/', str_replace('Modx3ProfilerToolbar\\', '', $class)) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Mock modX (не extends, чтобы не требовать MODX)
class MockModX
{
    public $context;
    public $user;
    public $parser;
    public $services;
    public $resource;
    public $resourceGenerated = false;
    
    private $options = [];
    
    public function __construct()
    {
        // Не вызываем родительский конструктор
        $this->context = (object)['key' => 'web'];
        $this->user = (object)['id' => 1, 'hasSessionContext' => fn($ctx) => true];
        $this->services = (object)['has' => fn($name) => false, 'get' => fn($name) => null];
        $this->parser = null;
        $this->resource = (object)['_output' => ''];
        
        $this->options = [
            'modx3profilertoolbar.enabled' => true,
            'modx3profilertoolbar.sampling_rate' => 100,
            'modx3profilertoolbar.cli_enabled' => true,
            'modx3profilertoolbar.cli_output' => 'console',
            'modx3profilertoolbar.cli_output_file' => 'logs/cli-trace.log',
            'modx3profilertoolbar.cli_output_format' => 'json',
            'modx3profilertoolbar.slow_query_ms' => 100,
            'core_path' => '/tmp/modx_test/',
        ];
    }
    
    public function getOption($key, $default = null, $strict = false)
    {
        return $this->options[$key] ?? $default;
    }
    
    public function setOption($key, $value)
    {
        $this->options[$key] = $value;
    }
    
    public function log($level, $message) {}
    public function getObject($class, $criteria) { return null; }
}

$passed = 0;
$failed = 0;

function test($condition, $message) {
    global $passed, $failed;
    if ($condition) {
        echo "✓ $message\n";
        $passed++;
    } else {
        echo "✗ $message\n";
        $failed++;
    }
}

// Проверка существования класса
echo "--- Class Existence ---\n";
test(class_exists('Modx3ProfilerToolbar\Service\ProfilerService'), 'ProfilerService class exists');

if (!class_exists('Modx3ProfilerToolbar\Service\ProfilerService')) {
    echo "\nFATAL: ProfilerService not found. Exiting.\n";
    exit(1);
}

// Создаём mock и экземпляр
$modx = new MockModX();
$profiler = new \Modx3ProfilerToolbar\Service\ProfilerService($modx);
test($profiler instanceof \Modx3ProfilerToolbar\Service\ProfilerService, 'ProfilerService instantiated');

// Проверяем свойства через Reflection
echo "\n--- Properties Check ---\n";
$reflection = new ReflectionClass($profiler);

$properties = [
    'cliOutput' => 'console',
    'cliOutputFile' => null,
    'cliOutputFormat' => 'json',
];

foreach ($properties as $propName => $expectedDefault) {
    if ($reflection->hasProperty($propName)) {
        $prop = $reflection->getProperty($propName);
        $prop->setAccessible(true);
        $value = $prop->getValue($profiler);
        test($value === $expectedDefault || ($expectedDefault === null && $value === null), 
             "$propName = " . var_export($value, true));
    } else {
        test(false, "$propName EXISTS");
    }
}

// Проверяем методы
echo "\n--- Methods Check ---\n";
$methods = [
    'outputCliTrace',
    'formatTraceAsText',
    'formatTraceAsCsv',
    'formatBytes',
    'escapeCsvField',
];

foreach ($methods as $methodName) {
    test($reflection->hasMethod($methodName), "Method $methodName exists");
}

// Тестируем formatBytes
echo "\n--- Test: formatBytes ---\n";
$method = $reflection->getMethod('formatBytes');
$method->setAccessible(true);

test($method->invoke($profiler, 1024) === '1 KB', '1024 bytes = 1 KB');
test($method->invoke($profiler, 1048576) === '1 MB', '1048576 bytes = 1 MB');
test($method->invoke($profiler, 1073741824) === '1 GB', '1073741824 bytes = 1 GB');
test($method->invoke($profiler, 500) === '500 B', '500 bytes = 500 B');
test($method->invoke($profiler, 1536) === '1.5 KB', '1536 bytes = 1.5 KB');

// Тестируем escapeCsvField
echo "\n--- Test: escapeCsvField ---\n";
$method = $reflection->getMethod('escapeCsvField');
$method->setAccessible(true);

test($method->invoke($profiler, 'Simple') === 'Simple', 'Simple text unchanged');
test($method->invoke($profiler, 'Hello, World') === '"Hello, World"', 'Comma escaped');
test($method->invoke($profiler, 'Say "Hi"') === '"Say ""Hi"""', 'Quotes doubled');
test(strpos($method->invoke($profiler, "Line1\nLine2"), '"') === 0, 'Newline escaped');

// Тестируем formatTraceAsText
echo "\n--- Test: formatTraceAsText ---\n";
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
    'advisor' => [['message' => 'Performance is good']],
    'events' => [
        ['name' => 'OnMODXInit', 'duration' => 0.05, 'type' => 'event'],
        ['name' => 'TestSnippet', 'duration' => 0.03, 'type' => 'snippet', 'cached' => false],
    ],
    'sql' => [
        ['query' => 'SELECT * FROM modx_site_content WHERE id = 1', 'time' => 0.015],
    ],
];

$textOutput = $method->invoke($profiler, $sampleTrace);

test(strpos($textOutput, '--- Modx3ProfilerToolbar CLI Trace ---') !== false, 'Text has header');
test(strpos($textOutput, 'Request ID: test_') !== false, 'Text has request_id');
test(strpos($textOutput, 'Performance Score: 85') !== false, 'Text has score');
test(strpos($textOutput, '8 MB') !== false, 'Text has formatted memory');
test(strpos($textOutput, 'Events (2):') !== false, 'Text has event count');
test(strpos($textOutput, 'SQL Queries (1):') !== false, 'Text has SQL count');
test(strpos($textOutput, 'Performance is good') !== false, 'Text has advisor message');

// Тестируем formatTraceAsCsv
echo "\n--- Test: formatTraceAsCsv ---\n";
$method = $reflection->getMethod('formatTraceAsCsv');
$method->setAccessible(true);

$csvOutput = $method->invoke($profiler, $sampleTrace);

test(strpos($csvOutput, 'Name,Type,Duration(ms),Cached') === 0, 'CSV has correct header');
test(substr_count($csvOutput, "\n") >= 2, 'CSV has multiple lines');

// Проверяем экранирование в CSV
$csvWithSpecial = $method->invoke($profiler, [
    'events' => [
        ['name' => 'Test, "Special"', 'duration' => 0.01, 'type' => 'event', 'cached' => ''],
    ],
    'sql' => [],
]);
test(strpos($csvWithSpecial, '"Test, ""Special"""') !== false, 'CSV escapes special chars');

// Тестируем outputCliTrace с JSON
echo "\n--- Test: outputCliTrace (JSON) ---\n";
$modx->setOption('modx3profilertoolbar.cli_output_format', 'json');
$profiler = new \Modx3ProfilerToolbar\Service\ProfilerService($modx);
$reflection = new ReflectionClass($profiler);
$method = $reflection->getMethod('outputCliTrace');
$method->setAccessible(true);

$traceData = [
    'request_id' => 'json_test',
    'context' => 'cli',
    'url' => '/test',
    'method' => 'CLI',
    'metrics' => ['total_time' => 0.1],
];

ob_start();
$method->invoke($profiler, $traceData);
$output = ob_get_clean();

$decoded = json_decode($output, true);
test($decoded !== null, 'JSON output is valid');
test(($decoded['request_id'] ?? '') === 'json_test', 'JSON has correct request_id');

// Тестируем outputCliTrace с file output
echo "\n--- Test: outputCliTrace (File) ---\n";
$testDir = '/tmp/modx_test/components/modx3profilertoolbar/logs';
@mkdir($testDir, 0755, true);

$modx->setOption('modx3profilertoolbar.cli_output', 'file');
$modx->setOption('modx3profilertoolbar.cli_output_file', 'logs/test-output.log');
$modx->setOption('modx3profilertoolbar.cli_output_format', 'json');
$profiler = new \Modx3ProfilerToolbar\Service\ProfilerService($modx);
$reflection = new ReflectionClass($profiler);
$method = $reflection->getMethod('outputCliTrace');
$method->setAccessible(true);

$traceData = ['request_id' => 'file_test'];
$method->invoke($profiler, $traceData);

$testFile = $testDir . '/test-output.log';
test(file_exists($testFile), 'File output created');
if (file_exists($testFile)) {
    $content = file_get_contents($testFile);
    test(strpos($content, 'file_test') !== false, 'File contains request_id');
    @unlink($testFile);
}

// Тестируем безопасность путей
echo "\n--- Test: Path Security ---\n";
$modx->setOption('modx3profilertoolbar.cli_output_file', '../../../etc/passwd');
$profiler = new \Modx3ProfilerToolbar\Service\ProfilerService($modx);
$reflection = new ReflectionClass($profiler);
$method = $reflection->getMethod('outputCliTrace');
$method->setAccessible(true);

ob_start();
$method->invoke($profiler, ['request_id' => 'security_test']);
ob_get_clean();

test(!file_exists('/etc/passwd.log'), 'Path traversal blocked');

// Итоги
echo "\n=== Results ===\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";

if ($failed === 0) {
    echo "\n✓ All tests passed!\n";
    exit(0);
} else {
    echo "\n✗ Some tests failed.\n";
    exit(1);
}

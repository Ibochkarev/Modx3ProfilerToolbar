<?php

/**
 * Тест CLI вывода ProfilerService
 *
 * Запуск:
 *   php tests/cli_output_test.php
 *
 * Проверки:
 * 1. Корректность форматирования JSON
 * 2. Корректность форматирования Text
 * 3. Корректность форматирования CSV
 * 4. Безопасность путей к файлам
 * 5. Экранирование CSV полей
 */

// Mock modX для тестирования без полного MODX
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
        $this->context = (object)['key' => 'web'];
        $this->user = (object)['id' => 1, 'hasSessionContext' => fn($ctx) => true];
        $this->services = (object)['has' => fn($name) => false, 'get' => fn($name) => null];
        $this->parser = null;
        $this->resource = (object)['_output' => ''];

        // Default options
        $this->options = [
            'modx3profilertoolbar.enabled' => true,
            'modx3profilertoolbar.sampling_rate' => 100,
            'modx3profilertoolbar.cli_enabled' => true,
            'modx3profilertoolbar.cli_output' => 'console',
            'modx3profilertoolbar.cli_output_file' => '{core_path}components/modx3profilertoolbar/logs/cli-trace.log',
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

    public function log($level, $message)
    {
        // Silent for tests
    }

    public function getObject($class, $criteria)
    {
        return null;
    }
}

// Autoload для классов ProfilerService
spl_autoload_register(function ($class) {
    $base = __DIR__ . '/../core/components/modx3profilertoolbar/src/';
    $file = $base . str_replace('\\', '/', str_replace('Modx3ProfilerToolbar\\', '', $class)) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

require_once __DIR__ . '/../core/components/modx3profilertoolbar/src/Service/ProfilerService.php';
require_once __DIR__ . '/../core/components/modx3profilertoolbar/src/Service/CollectorService.php';
require_once __DIR__ . '/../core/components/modx3profilertoolbar/src/Service/AnalyzerService.php';

use Modx3ProfilerToolbar\Service\ProfilerService;

class CliOutputTest
{
    private $passed = 0;
    private $failed = 0;

    public function run()
    {
        echo "=== Modx3ProfilerToolbar CLI Output Tests ===\n\n";

        $this->testJsonFormat();
        $this->testTextFormat();
        $this->testCsvFormat();
        $this->testCsvEscaping();
        $this->testBytesFormatting();
        $this->testFilePathSecurity();

        echo "\n=== Results ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";

        return $this->failed === 0;
    }

    private function assert($condition, $message)
    {
        if ($condition) {
            echo "✓ $message\n";
            $this->passed++;
        } else {
            echo "✗ $message\n";
            $this->failed++;
        }
    }

    private function testJsonFormat()
    {
        echo "\n--- Test: JSON Format ---\n";

        $modx = new MockModX();
        $modx->setOption('modx3profilertoolbar.cli_output_format', 'json');

        $profiler = new ProfilerService($modx);

        // Используем Reflection для вызова защищённого метода
        $reflection = new ReflectionClass($profiler);
        $method = $reflection->getMethod('outputCliTrace');
        $method->setAccessible(true);

        $traceData = [
            'request_id' => 'test123',
            'context' => 'cli',
            'url' => '/cli/test',
            'method' => 'CLI',
            'metrics' => [
                'total_time' => 0.123,
                'php_time' => 0.1,
                'sql_time' => 0.023,
                'memory_peak' => 10485760,
            ],
            'sql_count' => 2,
            'snippet_count' => 3,
            'chunk_count' => 1,
            'performance_score' => 85,
            'advisor' => ['message' => 'Good performance'],
            'events' => [
                ['name' => 'OnMODXInit', 'duration' => 0.05, 'type' => 'event'],
            ],
            'sql' => [
                ['query' => 'SELECT * FROM modx_resources', 'time' => 0.01],
            ],
        ];

        ob_start();
        $method->invoke($profiler, $traceData);
        $output = ob_get_clean();

        $decoded = json_decode($output, true);
        $this->assert($decoded !== null, 'JSON декодируется без ошибок');
        $this->assert(isset($decoded['request_id']), 'JSON содержит request_id');
        $this->assert($decoded['request_id'] === 'test123', 'request_id корректен');
        $this->assert(isset($decoded['performance_score']), 'JSON содержит performance_score');
    }

    private function testTextFormat()
    {
        echo "\n--- Test: Text Format ---\n";

        $modx = new MockModX();
        $modx->setOption('modx3profilertoolbar.cli_output_format', 'text');

        $profiler = new ProfilerService($modx);

        $reflection = new ReflectionClass($profiler);
        $method = $reflection->getMethod('outputCliTrace');
        $method->setAccessible(true);

        $traceData = [
            'request_id' => 'test456',
            'context' => 'cli',
            'url' => '/cli/test',
            'method' => 'CLI',
            'metrics' => [
                'total_time' => 0.123,
                'php_time' => 0.1,
                'sql_time' => 0.023,
                'memory_peak' => 10485760,
            ],
            'sql_count' => 2,
            'snippet_count' => 3,
            'chunk_count' => 1,
            'performance_score' => 75,
            'advisor' => ['message' => 'Consider optimization'],
            'events' => [
                ['name' => 'OnMODXInit', 'duration' => 0.05, 'type' => 'event'],
                ['name' => 'Snippet1', 'duration' => 0.03, 'type' => 'snippet', 'cached' => false],
            ],
            'sql' => [
                ['query' => 'SELECT * FROM modx_resources', 'time' => 0.01],
            ],
        ];

        ob_start();
        $method->invoke($profiler, $traceData);
        $output = ob_get_clean();

        $this->assert(strpos($output, '--- Modx3ProfilerToolbar CLI Trace ---') !== false, 'Text содержит заголовок');
        $this->assert(strpos($output, 'Request ID: test456') !== false, 'Text содержит request_id');
        $this->assert(strpos($output, 'Performance Score: 75') !== false, 'Text содержит performance score');
        $this->assert(strpos($output, 'Events (2):') !== false, 'Text содержит количество событий');
        $this->assert(strpos($output, 'SQL Queries (1):') !== false, 'Text содержит количество SQL запросов');
        $this->assert(strpos($output, '10 MB') !== false, 'Text содержит форматированный размер памяти');
    }

    private function testCsvFormat()
    {
        echo "\n--- Test: CSV Format ---\n";

        $modx = new MockModX();
        $modx->setOption('modx3profilertoolbar.cli_output_format', 'csv');

        $profiler = new ProfilerService($modx);

        $reflection = new ReflectionClass($profiler);
        $method = $reflection->getMethod('outputCliTrace');
        $method->setAccessible(true);

        $traceData = [
            'request_id' => 'test789',
            'events' => [
                ['name' => 'OnMODXInit', 'duration' => 0.05, 'type' => 'event'],
            ],
            'sql' => [
                ['query' => 'SELECT * FROM modx_resources', 'time' => 0.01],
            ],
        ];

        ob_start();
        $method->invoke($profiler, $traceData);
        $output = ob_get_clean();

        $lines = explode("\n", trim($output));
        $this->assert(count($lines) >= 2, 'CSV содержит заголовок и данные');
        $this->assert(strpos($lines[0], 'Name,Type,Duration(ms),Cached') === 0, 'CSV заголовок корректен');
    }

    private function testCsvEscaping()
    {
        echo "\n--- Test: CSV Escaping ---\n";

        $modx = new MockModX();
        $profiler = new ProfilerService($modx);

        $reflection = new ReflectionClass($profiler);
        $method = $reflection->getMethod('escapeCsvField');
        $method->setAccessible(true);

        // Тест 1: поле с запятой
        $result = $method->invoke($profiler, 'Hello, World');
        $this->assert($result === '"Hello, World"', 'Запятая экранируется кавычками');

        // Тест 2: поле с кавычкой
        $result = $method->invoke($profiler, 'Say "Hello"');
        $this->assert($result === '"Say ""Hello"""', 'Кавычки удваиваются');

        // Тест 3: поле с переносом строки
        $result = $method->invoke($profiler, "Line1\nLine2");
        $this->assert(strpos($result, '"') === 0, 'Перенос строки экранируется');

        // Тест 4: обычное поле
        $result = $method->invoke($profiler, 'SimpleText');
        $this->assert($result === 'SimpleText', 'Обычное поле не изменяется');
    }

    private function testBytesFormatting()
    {
        echo "\n--- Test: Bytes Formatting ---\n";

        $modx = new MockModX();
        $profiler = new ProfilerService($modx);

        $reflection = new ReflectionClass($profiler);
        $method = $reflection->getMethod('formatBytes');
        $method->setAccessible(true);

        $this->assert($method->invoke($profiler, 1024) === '1 KB', '1024 байт = 1 KB');
        $this->assert($method->invoke($profiler, 1048576) === '1 MB', '1048576 байт = 1 MB');
        $this->assert($method->invoke($profiler, 1073741824) === '1 GB', '1073741824 байт = 1 GB');
        $this->assert($method->invoke($profiler, 500) === '500 B', '500 байт = 500 B');
    }

    private function testFilePathSecurity()
    {
        echo "\n--- Test: File Path Security ---\n";

        $modx = new MockModX();
        $modx->setOption('modx3profilertoolbar.cli_output', 'file');
        $modx->setOption('modx3profilertoolbar.cli_output_file', '../../../etc/passwd');
        $modx->setOption('modx3profilertoolbar.cli_output_format', 'json');

        $profiler = new ProfilerService($modx);

        $reflection = new ReflectionClass($profiler);
        $method = $reflection->getMethod('outputCliTrace');
        $method->setAccessible(true);

        $traceData = ['request_id' => 'security_test'];

        // Создаём временную дирекорию для теста
        $testDir = '/tmp/modx_test/components/modx3profilertoolbar/logs';
        @mkdir($testDir, 0755, true);

        ob_start();
        $method->invoke($profiler, $traceData);
        $output = ob_get_clean();

        // Проверяем, что файл НЕ был создан в /etc
        $this->assert(!file_exists('/etc/passwd.log'), 'Попытка записи в /etc заблокирована');

        // Проверяем, что файл создан в безопасной директории
        $safeFile = $testDir . '/cli-trace.log';
        $this->assert(file_exists($safeFile), 'Файл создан в безопасной директории');

        // Очищаем тестовые файлы
        @unlink($safeFile);
        @rmdir($testDir);
    }
}

// Запуск тестов
$test = new CliOutputTest();
$success = $test->run();

exit($success ? 0 : 1);

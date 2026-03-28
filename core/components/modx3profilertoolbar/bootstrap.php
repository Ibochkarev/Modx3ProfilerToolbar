<?php
/**
 * Modx3ProfilerToolbar bootstrap
 * Регистрация namespace и autoload для Modx3ProfilerToolbar\*
 *
 * @package modx3profilertoolbar
 */

defined('MODX_CORE_PATH') || exit;

// Определяем путь через namespace
$corePath = null;
$namespace = $modx->getObject('modNamespace', 'modx3profilertoolbar');
if ($namespace) {
    $corePath = $namespace->getCorePath();
}
if (!$corePath) {
    $corePath = $modx->getOption('core_path') . 'components/modx3profilertoolbar/';
}

// Загрузить интерфейс debugPdoParser глобально (для pdoTools совместимости)
if (!interface_exists('debugPdoParser', false)) {
    require_once $corePath . 'src/Parser/debugPdoParser.php';
}

spl_autoload_register(static function ($class) use ($corePath) {
    if (strpos($class, 'Modx3ProfilerToolbar\\') !== 0) {
        return;
    }
    $rel = str_replace('\\', '/', substr($class, strlen('Modx3ProfilerToolbar') + 1)) . '.php';
    $path = $corePath . 'src/' . $rel;
    if (is_file($path)) {
        require_once $path;
    }
});

return true;

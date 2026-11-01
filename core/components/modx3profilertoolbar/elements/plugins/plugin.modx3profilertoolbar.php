<?php
/**
 * Modx3ProfilerToolbar Main Plugin — оркестратор профилирования (только toolbar).
 * События: OnMODXInit, OnHandleRequest, OnLoadWebDocument, OnWebPagePrerender.
 *
 * @package modx3profilertoolbar
 */

$corePath = $modx->getOption('modx3profilertoolbar.core_path', null, null);
if (!$corePath) {
    // Автоматически определяем путь через namespace
    $namespace = $modx->getObject('modNamespace', 'modx3profilertoolbar');
    if ($namespace) {
        $corePath = $namespace->getCorePath();
    }
}
if (!$corePath) {
    // Fallback на стандартный путь
    $corePath = $modx->getOption('core_path') . 'components/modx3profilertoolbar/';
}

if (!class_exists('Modx3ProfilerToolbar\Service\ProfilerService')) {
    require_once $corePath . 'bootstrap.php';
}

// Проверяем, включен ли профайлер
if (!(bool) $modx->getOption('modx3profilertoolbar.enabled', null, false)) {
    return '';
}

$eventName = $modx->event->name;

switch ($eventName) {
    case 'OnMODXInit':
        if (class_exists('Modx3ProfilerToolbar\Service\ProfilerService')) {
            $profiler = new \Modx3ProfilerToolbar\Service\ProfilerService($modx);
            $modx->modx3profilertoolbar = $profiler;
            $profiler->start();
        }
        break;

    case 'OnHandleRequest':
        if (!empty($modx->modx3profilertoolbar)) {
            // Подмена парсера только если профайлер действительно запущен (requestId установлен)
            // и парсер уже создан (к моменту OnHandleRequest он гарантированно существует)
            if ($modx->modx3profilertoolbar->getRequestId()) {
                $modx->modx3profilertoolbar->replaceParser();
            }
            
            $modx->modx3profilertoolbar->trackEvent('OnHandleRequest', 'start');
            // Close the OnMODXInit event here to get a proper duration
            $modx->modx3profilertoolbar->trackEvent('OnMODXInit', 'end');
        }
        break;
    case 'OnLoadWebDocument':
        if (!empty($modx->modx3profilertoolbar)) {
            $modx->modx3profilertoolbar->trackEvent('OnHandleRequest', 'end');
            $modx->modx3profilertoolbar->trackEvent('OnLoadWebDocument', 'start');
        }
        break;

    case 'OnWebPagePrerender':
        if (!empty($modx->modx3profilertoolbar)) {
            $modx->modx3profilertoolbar->trackEvent('OnLoadWebDocument', 'end');
            $modx->modx3profilertoolbar->trackEvent('OnWebPagePrerender', 'start');
            $modx->modx3profilertoolbar->trackEvent('OnWebPagePrerender', 'end');
            $modx->modx3profilertoolbar->stop();
        }
        break;
}

return '';

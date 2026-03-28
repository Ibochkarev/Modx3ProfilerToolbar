# Установка Modx3ProfilerToolbar

## Требования

| Компонент | Версия |
|-----------|--------|
| PHP | 8.1+ |
| MODX Revolution | 3.x |

Админка и VueTools не требуются — дополнение только с тулбаром на фронте.

## Установка через ModStore

1. Откройте Manager.
2. Перейдите в **Пакеты → Установщик** (Extras → Installer).
3. Найдите **Modx3ProfilerToolbar** (Performance Toolbar).
4. Нажмите **Установить**.

## Ручная установка

1. Соберите пакет из исходников:
   ```bash
   cd /path/to/ModProfilerFree
   php _build/build.php
   ```
   При необходимости создайте `_build/build.config.php` из примера и укажите `MODX_CORE_PATH`.

2. Скачайте транспорт: откройте в браузере `http://yoursite/_build/build.php?download=1` или возьмите файл из `core/packages/` (имя вида `modx3profilertoolbar-1.0.0-pl.transport.zip`).

3. Поместите `.transport.zip` в каталог `core/packages/` установки MODX.

4. В Manager: **Пакеты → Установщик** → «Установить из существующих» → выберите пакет Modx3ProfilerToolbar → **Установить**.

## Проверка установки

1. Включите настройки **modx3profilertoolbar.enabled** и **modx3profilertoolbar.frontend_toolbar** (**Управление → Системные настройки**, фильтр **modx3profilertoolbar**).
2. Войдите в Manager и откройте любую страницу сайта (контекст web).
3. В правом нижнем углу должна появиться панель Dev Toolbar с метриками запроса.

Трейсы нигде не сохраняются — тулбар показывает только данные текущего запроса.

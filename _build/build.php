<?php
/**
 * Modx3ProfilerToolbar build script
 *
 * @package modx3profilertoolbar
 */

use MODX\Revolution\modX;
use MODX\Revolution\modCategory;
use MODX\Revolution\Transport\modPackageBuilder;
use MODX\Revolution\Transport\modTransportPackage;
use MODX\Revolution\modSystemSetting;
use MODX\Revolution\modMenu;
use MODX\Revolution\modPlugin;
use MODX\Revolution\modPluginEvent;
use xPDO\Transport\xPDOTransport;

class Modx3ProfilerToolbarPackage
{
    private $modx;
    private $config = [];
    private $category;
    private $category_attributes = [];

    public $builder;

    /**
     * Modx3ProfilerToolbarPackage constructor.
     *
     * @param modX $modx
     * @param array $config
     */
    public function __construct(modX $modx, array $config = [])
    {
        $this->modx = $modx;
        $this->modx->initialize('mgr');

        $root = dirname(__FILE__, 2) . '/';
        $core = $root . 'core/components/' . $config['name_lower'] . '/';
        $assets = $root . 'assets/components/' . $config['name_lower'] . '/';

        $this->config = array_merge([
            'log_level' => modX::LOG_LEVEL_INFO,
            'log_target' => XPDO_CLI_MODE ? 'ECHO' : 'HTML',

            'root' => $root,
            'build' => $root . '_build/',
            'elements' => $root . '_build/elements/',
            'data' => $root . '_build/data/',
            'resolvers' => $root . '_build/resolvers/',
            'core' => $core,
            'assets' => $assets,
        ], $config);

        $this->modx->setLogLevel($this->config['log_level']);
        $this->modx->setLogTarget($this->config['log_target']);

        $this->initialize();
    }

    /**
     * @return modPackageBuilder
     */
    public function process()
    {
        $this->encryptionSetup();

        // System settings
        if (file_exists($this->config['data'] . 'transport.settings.php')) {
            $this->settings();
        }

        // Plugin (adds to category when encrypt, separate vehicle otherwise)
        if (file_exists($this->config['data'] . 'transport.plugin.php')) {
            $this->plugin();
        }

        // Create main vehicle
        $vehicle = $this->builder->createVehicle($this->category, $this->category_attributes);

        // Files resolvers (сначала копируем файлы)
        $vehicle->resolve('file', [
            'source' => $this->config['core'],
            'target' => "return MODX_CORE_PATH . 'components/';",
        ]);
        $vehicle->resolve('file', [
            'source' => $this->config['assets'],
            'target' => "return MODX_ASSETS_PATH . 'components/';",
        ]);

        // Резолвер метрик (анонимная статистика установки)
        $this->builder->putVehicle($this->builder->createVehicle(
            ['source' => $this->config['resolvers'] . 'resolver_metrics.php'],
            ['vehicle_class' => \xPDO\Transport\xPDOScriptVehicle::class]
        ));
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Added metrics resolver');

        $this->builder->putVehicle($vehicle);

        if (!empty($this->config['encrypt'])) {
            $this->builder->putVehicle($this->builder->createVehicle(
                ['source' => $this->config['resolvers'] . 'resolve.encryption.php'],
                ['vehicle_class' => \xPDO\Transport\xPDOScriptVehicle::class]
            ));
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Added encryption resolver (for uninstall)');
        }

        $this->builder->setPackageAttributes([
            'changelog' => file_exists($this->config['core'] . 'docs/changelog.txt')
                ? file_get_contents($this->config['core'] . 'docs/changelog.txt') : '',
            'license' => file_exists($this->config['core'] . 'docs/license.txt')
                ? file_get_contents($this->config['core'] . 'docs/license.txt') : '',
            'readme' => file_exists($this->config['root'] . 'README.md')
                ? file_get_contents($this->config['root'] . 'README.md') : '',
            'requires' => [
                'php' => '>=8.1.0',
                'modx' => '>=3.0.0',
            ],
        ]);
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Added package attributes and setup options.');

        $this->modx->log(modX::LOG_LEVEL_INFO, 'Packing up transport package zip...');
        $this->builder->pack();

        if (!empty($this->config['install'])) {
            $this->install();
        }

        return $this->builder;
    }

    /**
     * Initialize package builder
     */
    private function initialize()
    {
        $this->builder = new modPackageBuilder($this->modx);
        $this->builder->createPackage($this->config['name_lower'], $this->config['version'], $this->config['release']);
        $this->builder->registerNamespace($this->config['name_lower'], false, true, '{core_path}components/' . $this->config['name_lower'] . '/');
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Created Transport Package and Namespace.');

        $this->category = $this->modx->newObject(modCategory::class);
        $this->category->set('category', $this->config['name']);
        $this->category_attributes = [
            xPDOTransport::UNIQUE_KEY => 'category',
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::RELATED_OBJECTS => false,
            xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [],
        ];
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Created main Category.');
    }

    /**
     * Setup encryption: add EncryptedVehicle class and resolver to package.
     * When encrypt is true, plugin is added to category as related object.
     */
    private function encryptionSetup()
    {
        if (empty($this->config['encrypt'])) {
            return;
        }

        $this->modx->log(modX::LOG_LEVEL_INFO, 'Encryption enabled.');

        // Load EncryptedVehicle class for build process
        require_once $this->config['core'] . 'src/Transport/EncryptedVehicle.php';

        // Add EncryptedVehicle class file to package (must be installed before encrypted vehicles)
        $this->builder->package->put(
            [
                'source' => $this->config['core'] . 'src/Transport/EncryptedVehicle.php',
                'target' => "return MODX_CORE_PATH . 'components/" . $this->config['name_lower'] . "/src/Transport/';",
            ],
            [
                'vehicle_class' => \xPDO\Transport\xPDOFileVehicle::class,
                xPDOTransport::UNINSTALL_FILES => false,
            ]
        );
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Added EncryptedVehicle class to package');

        // Add encryption resolver (runs before encrypted category vehicle on install)
        $this->builder->putVehicle($this->builder->createVehicle(
            ['source' => $this->config['resolvers'] . 'resolve.encryption.php'],
            ['vehicle_class' => \xPDO\Transport\xPDOScriptVehicle::class]
        ));
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Added encryption resolver');

        // Set encrypted vehicle class for the category
        $this->category_attributes['vehicle_class'] = 'Modx3ProfilerToolbar\\Transport\\EncryptedVehicle';
        $this->category_attributes[xPDOTransport::ABORT_INSTALL_ON_VEHICLE_FAIL] = true;
        $this->category_attributes[xPDOTransport::RELATED_OBJECTS] = true;
        $this->category_attributes[xPDOTransport::RELATED_OBJECT_ATTRIBUTES] = [
            'Plugins' => [
                xPDOTransport::PRESERVE_KEYS => false,
                xPDOTransport::UPDATE_OBJECT => !empty($this->config['update']['plugins']),
                xPDOTransport::UNIQUE_KEY => 'name',
                xPDOTransport::RELATED_OBJECTS => true,
                xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
                    'PluginEvents' => [
                        xPDOTransport::PRESERVE_KEYS => true,
                        xPDOTransport::UPDATE_OBJECT => true,
                        xPDOTransport::UNIQUE_KEY => ['pluginid', 'event'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Install package
     */
    private function install()
    {
        $signature = $this->builder->getSignature();
        $sig = explode('-', $signature);
        $versionSignature = explode('.', $sig[1]);

        /** @var modTransportPackage $package */
        $package = $this->modx->getObject(modTransportPackage::class, ['signature' => $signature]);
        if (!$package) {
            $package = $this->modx->newObject(modTransportPackage::class);
            $package->set('signature', $signature);
            $package->fromArray([
                'created' => date('Y-m-d H:i:s'),
                'updated' => null,
                'state' => 1,
                'workspace' => 1,
                'provider' => 0,
                'source' => $signature . '.transport.zip',
                'package_name' => $this->config['name'],
                'version_major' => $versionSignature[0],
                'version_minor' => !empty($versionSignature[1]) ? $versionSignature[1] : 0,
                'version_patch' => !empty($versionSignature[2]) ? $versionSignature[2] : 0,
            ]);
            if (!empty($sig[2])) {
                $r = preg_split('#([0-9]+)#', $sig[2], -1, PREG_SPLIT_DELIM_CAPTURE);
                if (is_array($r) && !empty($r)) {
                    $package->set('release', $r[0]);
                    $package->set('release_index', (isset($r[1]) ? $r[1] : '0'));
                } else {
                    $package->set('release', $sig[2]);
                }
            }
            $package->save();
        }

        // MODX 3.x compatibility
        if (isset($package->xpdo->packages['Revolution'])) {
            $package->xpdo->packages['MODX\Revolution\\'] = $package->xpdo->packages['Revolution'];
        }

        if ($package->install()) {
            $this->modx->runProcessor('System/ClearCache');
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Package installed successfully');
        } else {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Package installation failed');
        }
    }

    /**
     * Add settings
     */
    private function settings()
    {
        $settingsData = include($this->config['data'] . 'transport.settings.php');
        if (!is_array($settingsData)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not load settings');
            return;
        }

        $attributes = [
            xPDOTransport::UNIQUE_KEY => 'key',
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => !empty($this->config['update']['settings']),
        ];

        foreach ($settingsData as $key => $data) {
            /** @var modSystemSetting $setting */
            $setting = $this->modx->newObject(modSystemSetting::class);
            $setting->fromArray(array_merge([
                'key' => $this->config['name_lower'] . '.' . $key,
                'namespace' => $this->config['name_lower'],
                'area' => 'default',
            ], $data), '', true, true);

            $vehicle = $this->builder->createVehicle($setting, $attributes);
            $this->builder->putVehicle($vehicle);
        }
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Packaged in ' . count($settingsData) . ' System Settings');
    }

    /**
     * Add plugin. When encrypt is true, adds to category as related object (encrypted with category).
     * Otherwise creates separate vehicle.
     */
    private function plugin()
    {
        $pluginData = include($this->config['data'] . 'transport.plugin.php');
        if (!is_array($pluginData) || empty($pluginData['name'])) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not load plugin data');
            return;
        }

        /** @var modPlugin $plugin */
        $plugin = $this->modx->newObject(modPlugin::class);
        $plugin->fromArray([
            'name' => $pluginData['name'],
            'description' => $pluginData['description'] ?? '',
            'category' => 0,
            'plugincode' => $this->getFileContent($this->config['core'] . 'elements/plugins/' . $pluginData['file'] . '.php'),
            'static' => false,
        ], '', true, true);

        $events = [];
        if (!empty($pluginData['events'])) {
            foreach ($pluginData['events'] as $eventName => $eventData) {
                /** @var modPluginEvent $event */
                $event = $this->modx->newObject(modPluginEvent::class);
                $event->fromArray([
                    'event' => $eventName,
                    'priority' => $eventData['priority'] ?? 0,
                    'propertyset' => $eventData['propertyset'] ?? 0,
                ], '', true, true);
                $events[] = $event;
            }
        }
        if (!empty($events)) {
            $plugin->addMany($events);
        }

        if (!empty($this->config['encrypt'])) {
            $this->category->addMany([$plugin]);
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Packaged in Plugin (as category related, encrypted)');
        } else {
            $attributes = [
                xPDOTransport::UNIQUE_KEY => 'name',
                xPDOTransport::PRESERVE_KEYS => false,
                xPDOTransport::UPDATE_OBJECT => !empty($this->config['update']['plugins']),
                xPDOTransport::RELATED_OBJECTS => true,
                xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
                    'PluginEvents' => [
                        xPDOTransport::PRESERVE_KEYS => true,
                        xPDOTransport::UPDATE_OBJECT => true,
                        xPDOTransport::UNIQUE_KEY => ['pluginid', 'event'],
                    ],
                ],
            ];
            $vehicle = $this->builder->createVehicle($plugin, $attributes);
            $this->builder->putVehicle($vehicle);
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Packaged in Plugin');
        }
    }

    /**
     * Add menu (supports multiple items: main + submenus)
     */
    private function menu()
    {
        $menusData = include($this->config['data'] . 'transport.menu.php');
        if (!is_array($menusData)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not load menu data');
            return;
        }
        // Поддержка одного пункта: [[ ... ]] или нескольких: [[ ... ], [ ... ]]
        $list = isset($menusData[0]) && is_array($menusData[0]) ? $menusData : [$menusData];

        foreach ($list as $menuData) {
            if (empty($menuData['text'])) {
                continue;
            }
            /** @var modMenu $menu */
            $menu = $this->modx->newObject(modMenu::class);
            $menu->fromArray([
                'text' => $menuData['text'],
                'description' => $menuData['description'] ?? '',
                'parent' => $menuData['parent'] ?? 'components',
                'namespace' => $menuData['namespace'] ?? $this->config['name_lower'],
                'action' => $menuData['action'] ?? '',
                'icon' => $menuData['icon'] ?? '',
                'menuindex' => $menuData['menuindex'] ?? 0,
                'params' => $menuData['params'] ?? '',
                'handler' => $menuData['handler'] ?? '',
            ], '', true, true);

            $attributes = [
                xPDOTransport::PRESERVE_KEYS => true,
                xPDOTransport::UPDATE_OBJECT => !empty($this->config['update']['menus']),
                xPDOTransport::UNIQUE_KEY => 'text',
                xPDOTransport::RELATED_OBJECTS => false,
            ];

            $vehicle = $this->builder->createVehicle($menu, $attributes);
            $this->builder->putVehicle($vehicle);
        }
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Packaged in Menu');
    }

    /**
     * Get file content for element
     */
    private function getFileContent($filename)
    {
        if (file_exists($filename)) {
            $file = trim(file_get_contents($filename));
            return preg_match('#\<\?php(.*)#is', $file, $data)
                ? rtrim(rtrim(trim(@$data[1]), '?>'))
                : $file;
        }
        return '';
    }
}

/** @var array $config */
if (!file_exists(dirname(__FILE__) . '/config.inc.php')) {
    exit('Could not load config.inc.php!');
}
$config = require(dirname(__FILE__) . '/config.inc.php');

if (!defined('MODX_CORE_PATH') || !is_dir(MODX_CORE_PATH)) {
    exit('Define MODX_CORE_PATH in _build/config.inc.php');
}

require_once MODX_CORE_PATH . 'model/modx/modx.class.php';

$start = microtime(true);
$modx = new modX();
$installer = new Modx3ProfilerToolbarPackage($modx, $config);
$builder = $installer->process();
$elapsed = microtime(true) - $start;

$modx->log(modX::LOG_LEVEL_INFO, sprintf("\nBuild completed in %.2f seconds\n", $elapsed));

// Download in browser
if (php_sapi_name() !== 'cli' && !empty($_GET['download'])) {
    $name = $builder->getSignature() . '.transport.zip';
    if ($content = file_get_contents(MODX_CORE_PATH . '/packages/' . $name)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . $name);
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($content));
        exit($content);
    }
}

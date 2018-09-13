<?php

namespace Skyroom;

use DI\Container;
use DI\ContainerBuilder;
use DownShift\WordPress\EventEmitterInterface;
use Skyroom\Adapter\PluginAdapterInterface;
use Skyroom\Menu\MainMenu;
use Skyroom\Menu\RoomSubmenu;
use Skyroom\Menu\SettingSubmenu;
use Skyroom\Menu\UserSubmenu;
use Skyroom\Repository\UserRepository;
use Skyroom\Util\AssetManager;
use Skyroom\Util\Internationalization;

/**
 * Plugin main class to build container and register wp hooks.
 *
 * @package Skyroom
 * @author  Hossein Sadeghi <ho.mysterious@gmail.com>
 */
class Plugin
{
    /**
     * @var Container
     */
    private $container;

    /**
     * Boot skyroom plugin
     *
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function boot()
    {
        $this->container = $this->buildContainer();
        $this->container->get(Internationalization::class)->loadTextDomain();

        if (!$this->isConfigured()) {
            $this->tearDown($this->container->get(EventEmitterInterface::class));

            return;
        }

        $this->registerHooks($this->container->get(EventEmitterInterface::class));
        $this->container->get(PluginAdapterInterface::class)->setup();
    }

    /**
     * Build container and add service and parameter definitions
     *
     * @return Container
     */
    private function buildContainer()
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions(__DIR__.'/../../config.php');

        return $builder->build();
    }

    /**
     * Register plugin hooks
     *
     * @param EventEmitterInterface $eventEmitter
     */
    private function registerHooks(EventEmitterInterface $eventEmitter)
    {
        // Register menus
        $eventEmitter->on('admin_menu', function () {
            $menu = $this->container->get(MainMenu::class);
            $menu->register(
                $this->container->get('plugin.url').'admin/images/icon-32x32.png',
                $this->container->get(RoomSubmenu::class),
                $this->container->get(UserSubmenu::class),
                $this->container->get(SettingSubmenu::class)
            );
        });

        // Enqueue assets
        $eventEmitter->on('admin_init', function () {
            $this->container->get(AssetManager::class)->adminAssets();
        });

        // User register hook
        $eventEmitter->on('user_register', function ($userId) {
            $user = get_user_by('id', $userId);
            $this->container->get(UserRepository::class)->addUser($user);
        });
    }

    /**
     * Called when plugin is not configured yet. Sets up necessary thing to allow
     * configuration and shows notice to user
     *
     * @param EventEmitterInterface $eventEmitter
     */
    public function tearDown(EventEmitterInterface $eventEmitter)
    {
        // Load main menu with only settings submenu
        $eventEmitter->on('admin_menu', function () {
            $menu = $this->container->get(MainMenu::class);
            $menu->register(
                $this->container->get('plugin.url').'admin/images/icon-32x32.png',
                $this->container->get(SettingSubmenu::class)
            );
        });

        $eventEmitter->on('admin_notices', function () {
            if ($GLOBALS['pagenow'] !== 'admin.php' && $_GET['page'] !== 'skyroom-settings') {
                echo '<div id="skyroom_errors" class="error notice is-dismissible">';
                echo '<p>';
                printf(__(
                    "<strong>Skyroom</strong> plugin activated successfully but it's not configured yet. Please go to <a href=\"%s\">skyroom settings</a> page and configure it to start working.",
                    'skyroom'
                ), esc_attr(menu_page_url('skyroom-settings', false)));
                echo '</p>';
                echo '</div>';
            }
        }, 20);

        // Enqueue assets
        $eventEmitter->on('admin_init', function () {
            $this->container->get(AssetManager::class)->adminAssets();
        });

    }

    /**
     * Check plugin is configured or not
     *
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     *
     * @return bool
     */
    public function isConfigured()
    {
        return !(empty($this->container->get('setting.site')) || empty($this->container->get('setting.key'))
            || empty($this->container->get('setting.plugin')));
    }
}
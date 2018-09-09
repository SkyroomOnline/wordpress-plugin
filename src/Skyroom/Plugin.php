<?php

namespace Skyroom;

use DI\ContainerBuilder;
use DownShift\WordPress\EventEmitter;
use DI\Container;

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
        $this->container->get('Internationalization')->loadTextDomain();

        if (empty($this->container->get('setting.site'))) {
            $this->tearDown($this->container->get('Events'));

            return;
        }

        $this->registerHooks($this->container->get('Events'));

        if (!empty($this->container->get('PluginAdapter'))) {
            $this->container->get('PluginAdapter')->setup();
        }
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
     * @param EventEmitter $eventEmitter
     */
    private function registerHooks(EventEmitter $eventEmitter)
    {
        // Register menus
        $eventEmitter->on('admin_menu', function () {
            $menu = $this->container->get('Skyroom\Menu\MainMenu');
            $menu->register(
                $this->container->get('plugin.url').'admin/images/icon-32x32.png',
                $this->container->get('Skyroom\Menu\RoomSubmenu'),
                $this->container->get('Skyroom\Menu\UserSubmenu'),
                $this->container->get('Skyroom\Menu\SettingSubmenu')
            );
        });

        // Enqueue assets
        $eventEmitter->on('admin_init', function () {
            $this->container->get('Skyroom\Util\AssetManager')->adminAssets();
        });

        // User register hook
        $eventEmitter->on('user_register', function ($userId) {
            $user = get_user_by('id', $userId);
            $this->container->get('Skyroom\Repository\UserRepository')->addUser($user);
        });
    }

    /**
     * Called when plugin is not configured yet. Sets up necessary thing to allow
     * configuration and shows notice to user
     *
     * @param EventEmitter $eventEmitter
     */
    public function tearDown(EventEmitter $eventEmitter)
    {
        // Load main menu with only settings submenu
        $eventEmitter->on('admin_menu', function () {
            $menu = $this->container->get('Skyroom\Menu\MainMenu');
            $menu->register(
                $this->container->get('plugin.url').'admin/images/icon-32x32.png',
                $this->container->get('Skyroom\Menu\SettingSubmenu')
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
            $this->container->get('Skyroom\Util\AssetManager')->adminAssets();
        });

    }
}
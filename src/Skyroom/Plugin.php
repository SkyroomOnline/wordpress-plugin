<?php

namespace Skyroom;

use DI\Container;
use DI\ContainerBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use DownShift\WordPress\EventEmitterInterface;
use Skyroom\Adapter\PluginAdapterInterface;
use Skyroom\Controller\SkyroomController;
use Skyroom\Controller\SyncTaskController;
use Skyroom\Entity\Event;
use Skyroom\Factory\DICallableFactory;
use Skyroom\Menu\EventSubmenu;
use Skyroom\Menu\MainMenu;
use Skyroom\Menu\RoomSubmenu;
use Skyroom\Menu\SettingSubmenu;
use Skyroom\Menu\SyncSubmenu;
use Skyroom\Menu\UserSubmenu;
use Skyroom\Repository\EventRepository;
use Skyroom\Repository\UserRepository;
use Skyroom\Shortcoes\UserEnrollmentShortcode;
use Skyroom\Tasks\SyncDataTaskRunner;
use Skyroom\Util\Activator;
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

        $this->checkUpdate();

        if (!$this->isConfigured()) {
            $this->tearDown($this->container->get(EventEmitterInterface::class));

            return;
        }

        $eventEmitter = $this->container->get(EventEmitterInterface::class);
        $this->registerHooks($eventEmitter);
        $this->registerShortcodes();
        $this->registerAjaxActions();
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
     *
     * @throws DependencyException
     * @throws NotFoundException
     */
    private function registerHooks(EventEmitterInterface $eventEmitter)
    {
        // Register menus
        $eventEmitter->on('admin_menu', function () {
            $menu = $this->container->get(MainMenu::class);
            $menu->register(
                $this->container->get(RoomSubmenu::class),
                $this->container->get(UserSubmenu::class),
                $this->container->get(EventSubmenu::class),
                $this->container->get(SyncSubmenu::class),
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

            try {
                $this->container->get(UserRepository::class)->addUser($user);

                $info = [
                    'user_id' => $userId,
                ];
                $event = new Event(
                    sprintf(__('"%s" registered in skyroom service', 'skyroom'), $user->user_login),
                    Event::SUCCESSFUL,
                    $info
                );
                $this->container->get(EventRepository::class)->save($event);

            } catch (\Exception $exception) {
                $info = [
                    'error_code' => $exception->getCode(),
                    'error_message' => $exception->getMessage(),
                    'user_id' => $userId,
                ];
                $event = new Event(
                    sprintf(__('Failed to register "%s" to skyroom service', 'skyroom'), $user->user_login),
                    Event::FAILED,
                    $info
                );
                $this->container->get(EventRepository::class)->save($event);
            }
        });

        // Register room redirection hook
        $eventEmitter->on('do_parse_request', [$this->container->get(SkyroomController::class), 'parseRequest'], 10, 2);
    }

    /**
     * Register plugin shortcodes
     *
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function registerShortcodes()
    {
        add_shortcode('SkyroomEnrollments', [$this->container->get(UserEnrollmentShortcode::class), 'display']);
    }

    /**
     * Register plugin ajax actions
     *
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function registerAjaxActions()
    {
        $syncTaskController = $this->container->get(SyncTaskController::class);
        add_action('wp_ajax_' . SyncTaskController::startActionIdentifier, [$syncTaskController, 'startSyncTask']);
        add_action('wp_ajax_' . SyncTaskController::statusActionIdentifier, [$syncTaskController, 'getSyncStatus']);
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
                $this->container->get(SettingSubmenu::class)
            );
        });

        $eventEmitter->on('admin_notices', function () {
            if ($GLOBALS['pagenow'] !== 'admin.php' || $_GET['page'] !== 'skyroom-settings') {
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

    /**
     * Check for plugin update and perform update if needed
     */
    public function checkUpdate()
    {
        $version = get_option('skyroom_db_version');
        if (version_compare(Activator::dbVersion, $version, '>')) {
            Activator::activate();
        }
    }
}
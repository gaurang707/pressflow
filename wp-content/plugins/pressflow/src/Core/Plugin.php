<?php

declare(strict_types=1);

namespace PressFlow\Core;

use PressFlow\Admin\Admin;
use PressFlow\Frontend\Frontend;

final class Plugin
{
    private static ?self $instance = null;

    private bool $registered = false;

    private function __construct()
    {
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function run(): void
    {
        if ($this->registered) {
            return;
        }

        $this->registered = true;

        add_action('plugins_loaded', [$this, 'boot']);
    }

    public function boot(): void
    {
        load_plugin_textdomain(
            'pressflow',
            false,
            dirname(plugin_basename(PRESSFLOW_FILE)) . '/languages'
        );

        (new Admin())->registerHooks();
        (new Frontend())->registerHooks();

        /**
         * Fires after the PressFlow foundation has loaded.
         *
         * @param self $plugin The plugin instance.
         */
        do_action('pressflow_loaded', $this);
    }

    private function __clone()
    {
    }

    public function __wakeup(): void
    {
        throw new \LogicException('Cannot unserialize the plugin instance.');
    }
}

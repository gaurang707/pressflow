<?php

declare(strict_types=1);

namespace PressFlow\Admin;

final class Admin
{
    private const STYLE_HANDLE = 'pressflow-admin';

    private const SCRIPT_HANDLE = 'pressflow-admin';

    public function registerHooks(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueStyles']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
    }

    public function enqueueStyles(): void
    {
        /**
         * Filters the dependencies for the PressFlow admin stylesheet.
         *
         * @param string[] $dependencies Stylesheet handles.
         */
        $dependencies = apply_filters('pressflow_admin_style_dependencies', []);

        wp_enqueue_style(
            self::STYLE_HANDLE,
            PRESSFLOW_URL . 'assets/admin/css/admin.css',
            is_array($dependencies) ? $dependencies : [],
            PRESSFLOW_VERSION
        );
    }

    public function enqueueScripts(): void
    {
        /**
         * Filters the dependencies for the PressFlow admin script.
         *
         * @param string[] $dependencies Script handles.
         */
        $dependencies = apply_filters('pressflow_admin_script_dependencies', []);

        wp_enqueue_script(
            self::SCRIPT_HANDLE,
            PRESSFLOW_URL . 'assets/admin/js/admin.js',
            is_array($dependencies) ? $dependencies : [],
            PRESSFLOW_VERSION,
            ['in_footer' => true]
        );
    }
}

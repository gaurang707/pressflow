<?php

declare(strict_types=1);

namespace PressFlow\Frontend;

final class Frontend
{
    private const STYLE_HANDLE = 'pressflow-frontend';

    private const SCRIPT_HANDLE = 'pressflow-frontend';

    public function registerHooks(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueStyles']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
    }

    public function enqueueStyles(): void
    {
        /**
         * Filters the dependencies for the PressFlow frontend stylesheet.
         *
         * @param string[] $dependencies Stylesheet handles.
         */
        $dependencies = apply_filters('pressflow_frontend_style_dependencies', []);

        wp_enqueue_style(
            self::STYLE_HANDLE,
            PRESSFLOW_URL . 'assets/frontend/css/frontend.css',
            is_array($dependencies) ? $dependencies : [],
            PRESSFLOW_VERSION
        );
    }

    public function enqueueScripts(): void
    {
        /**
         * Filters the dependencies for the PressFlow frontend script.
         *
         * @param string[] $dependencies Script handles.
         */
        $dependencies = apply_filters('pressflow_frontend_script_dependencies', []);

        wp_enqueue_script(
            self::SCRIPT_HANDLE,
            PRESSFLOW_URL . 'assets/frontend/js/frontend.js',
            is_array($dependencies) ? $dependencies : [],
            PRESSFLOW_VERSION,
            ['in_footer' => true]
        );
    }
}

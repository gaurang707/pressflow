<?php
/**
 * Plugin Name:       PressFlow - Secure Digital Newsroom and Media Distribution Plugin
 * Description:       Object-oriented foundation for the PressFlow WordPress plugin.
 * Version:           0.1.0
 * Requires at least: 7.0.3
 * Requires PHP:      8.1
 * Author:            PressFlow
 * Text Domain:       pressflow
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('PRESSFLOW_VERSION', '0.1.0');
define('PRESSFLOW_FILE', __FILE__);
define('PRESSFLOW_PATH', plugin_dir_path(__FILE__));
define('PRESSFLOW_URL', plugin_dir_url(__FILE__));

require_once PRESSFLOW_PATH . 'src/Support/Autoloader.php';

PressFlow\Support\Autoloader::register();

register_activation_hook(PRESSFLOW_FILE, [PressFlow\Lifecycle\Activator::class, 'activate']);
register_deactivation_hook(PRESSFLOW_FILE, [PressFlow\Lifecycle\Deactivator::class, 'deactivate']);

PressFlow\Core\Plugin::instance()->run();

<?php
/**
 * Configuration overrides for WP_ENV === 'localhost'
 */

use Roots\WPConfig\Config;
use function Env\env;

Config::define('SAVEQUERIES', true);
Config::define('WP_DEBUG', true);
Config::define('WP_DEBUG_DISPLAY', true);
Config::define('WP_DEBUG_LOG', env('WP_DEBUG_LOG') ?? true);
Config::define('WP_DISABLE_FATAL_ERROR_HANDLER', true);
Config::define('SCRIPT_DEBUG', true);
Config::define('DISALLOW_INDEXING', true);

ini_set('display_errors', '1');

// Enable plugin and theme updates and installation from the admin
Config::define('DISALLOW_FILE_MODS', false);

// Development env disabled plugins
Config::define('DISABLED_PLUGINS', [
    'spinupwp/spinupwp.php',
    'limit-login-attempts-reloaded/limit-login-attempts-reloaded.php',
	'jetpack/jetpack.php',
    'wp-mail-smtp-pro/wp_mail_smtp.php',
    'ithemes-security-pro/ithemes-security-pro.php',
    'analyticswp/analyticswp.php',
    'wp-rocket/wp-rocket.php'
]);
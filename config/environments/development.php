<?php

/**
 * Configuration overrides for WP_ENV === 'development'. Note DISALLOW_FILE_MODS
 * is NOT flipped back on here, unlike stock Bedrock — ADR-0003 keeps deploys
 * immutable in every environment, local dev included.
 */

use Roots\WPConfig\Config;

Config::define('SAVEQUERIES', true);
Config::define('WP_DEBUG', true);
Config::define('WP_DEBUG_DISPLAY', true);
Config::define('WP_DEBUG_LOG', true);
Config::define('SCRIPT_DEBUG', true);
Config::define('DISALLOW_INDEXING', true);

ini_set('display_errors', '1');

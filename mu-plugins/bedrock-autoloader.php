<?php

/**
 * Plugin Name:  Bedrock Autoloader
 * Plugin URI:   https://github.com/roots/bedrock-autoloader
 * Description:  Requires standard plugins the same way as must-use plugins — no
 *               `active_plugins` DB row needed, so activation needs no runtime
 *               write at all (ADR-0003: immutable deploys, read-only filesystem).
 * Author:       Roots
 * Author URI:   https://roots.io/
 * License:      MIT License
 */

namespace Roots\Bedrock;

if (is_blog_installed() && class_exists(Autoloader::class)) {
	new Autoloader();
}

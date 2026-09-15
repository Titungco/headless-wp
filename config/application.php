<?php

/**
 * Base configuration, following the Bedrock convention: this file holds
 * everything that doesn't vary by environment. config/environments/{WP_ENV}.php
 * holds the overrides that do.
 */

use Roots\WPConfig\Config;

use function Env\env;

Env\Env::$options
	= Env\Env::CONVERT_BOOL
	| Env\Env::CONVERT_NULL
	| Env\Env::CONVERT_INT
	| Env\Env::STRIP_QUOTES
	| Env\Env::LOCAL_FIRST;

/**
 * Directory containing all of the site's files
 *
 * @var string
 */
$root_dir = dirname(__DIR__);

/**
 * Document root
 *
 * @var non-falsy-string
 */
$webroot_dir = $root_dir . '/web';

/**
 * Use Dotenv to set required environment variables and load .env file in root.
 * In production these are already set on the container (ECS task env/secrets),
 * so a missing .env there is expected, not an error.
 */
if (file_exists($root_dir . '/.env')) {
	$repository = Dotenv\Repository\RepositoryBuilder::createWithNoAdapters()
		->addAdapter(Dotenv\Repository\Adapter\EnvConstAdapter::class)
		->addAdapter(Dotenv\Repository\Adapter\PutenvAdapter::class)
		->immutable()
		->make();

	$dotenv = Dotenv\Dotenv::create($repository, $root_dir);
	$dotenv->load();
	$dotenv->required(['WP_HOME', 'WP_SITEURL', 'DB_NAME', 'DB_USER', 'DB_PASSWORD']);
}

/**
 * WP_ENV: default 'production' — only local dev sets it to 'development' via .env.
 */
define('WP_ENV', env('WP_ENV') ?: 'production');

if (in_array(WP_ENV, ['production', 'development'], true)) {
	Config::define('WP_ENVIRONMENT_TYPE', WP_ENV);
}

/**
 * URLs
 */
Config::define('WP_HOME', env('WP_HOME'));
Config::define('WP_SITEURL', env('WP_SITEURL'));

/**
 * Custom content directory: web/app instead of the default web/wp/wp-content.
 */
Config::define('CONTENT_DIR', '/app');
Config::define('WP_CONTENT_DIR', $webroot_dir . Config::get('CONTENT_DIR'));
Config::define('WP_CONTENT_URL', Config::get('WP_HOME') . Config::get('CONTENT_DIR'));

/**
 * Database
 */
Config::define('DB_NAME', env('DB_NAME'));
Config::define('DB_USER', env('DB_USER'));
Config::define('DB_PASSWORD', env('DB_PASSWORD'));
Config::define('DB_HOST', env('DB_HOST') ?: 'localhost');
Config::define('DB_CHARSET', 'utf8mb4');
Config::define('DB_COLLATE', '');
$table_prefix = env('DB_PREFIX') ?: 'wp_';

/**
 * Authentication unique keys and salts
 */
Config::define('AUTH_KEY', env('AUTH_KEY'));
Config::define('SECURE_AUTH_KEY', env('SECURE_AUTH_KEY'));
Config::define('LOGGED_IN_KEY', env('LOGGED_IN_KEY'));
Config::define('NONCE_KEY', env('NONCE_KEY'));
Config::define('AUTH_SALT', env('AUTH_SALT'));
Config::define('SECURE_AUTH_SALT', env('SECURE_AUTH_SALT'));
Config::define('LOGGED_IN_SALT', env('LOGGED_IN_SALT'));
Config::define('NONCE_SALT', env('NONCE_SALT'));

/**
 * Redis object cache (rhubarbgroup/redis-cache). The object-cache.php drop-in
 * is copied into web/app/ at build time (see Dockerfile), not via the plugin's
 * runtime "Enable Object Cache" button — the filesystem is read-only.
 */
Config::define('WP_REDIS_HOST', env('REDIS_HOST') ?: 'redis');
Config::define('WP_REDIS_PORT', env('REDIS_PORT') ?: 6379);
Config::define('WP_REDIS_DATABASE', env('REDIS_DATABASE') ?: 0);
if (env('REDIS_PASSWORD')) {
	Config::define('WP_REDIS_PASSWORD', env('REDIS_PASSWORD'));
}
Config::define('WP_CACHE', true);

/**
 * Media on S3 (humanmade/s3-uploads). Local dev / MinIO uses explicit static
 * credentials; production has no static keys and relies on the ECS task role.
 */
Config::define('S3_UPLOADS_BUCKET', env('S3_UPLOADS_BUCKET') ?: '');
Config::define('S3_UPLOADS_REGION', env('S3_UPLOADS_REGION') ?: 'us-east-1');
if (env('S3_UPLOADS_KEY') && env('S3_UPLOADS_SECRET')) {
	Config::define('S3_UPLOADS_KEY', env('S3_UPLOADS_KEY'));
	Config::define('S3_UPLOADS_SECRET', env('S3_UPLOADS_SECRET'));
} else {
	Config::define('S3_UPLOADS_USE_INSTANCE_PROFILE', true);
}
// Custom S3-compatible endpoints (R2, MinIO, ...) often serve public files
// from a different URL than the API endpoint itself — mu-plugins/s3-endpoint.php
// points the API calls at S3_UPLOADS_ENDPOINT; this points generated media URLs
// at the actual public bucket URL.
if (env('S3_UPLOADS_BUCKET_URL')) {
	Config::define('S3_UPLOADS_BUCKET_URL', env('S3_UPLOADS_BUCKET_URL'));
}

/**
 * Auth0 (auth0/wordpress admin SSO). Constant prefix per the plugin's
 * documented AUTH0_ENV_* override convention.
 */
if (env('AUTH0_DOMAIN')) {
	Config::define('AUTH0_ENV_DOMAIN', env('AUTH0_DOMAIN'));
	Config::define('AUTH0_ENV_CLIENT_ID', env('AUTH0_CLIENT_ID'));
	Config::define('AUTH0_ENV_CLIENT_SECRET', env('AUTH0_CLIENT_SECRET'));
}

/**
 * Immutable deploys (ADR-0003): no plugin/theme installs, edits, or updates
 * at runtime, in any environment — code only changes by rebuilding the image.
 */
Config::define('AUTOMATIC_UPDATER_DISABLED', true);
Config::define('DISALLOW_FILE_EDIT', true);
Config::define('DISALLOW_FILE_MODS', true);

/**
 * WP-Cron (ADR-0006): disabled everywhere — nothing external triggers it yet
 * in phase 1. Do not flip this back on; see the ADR before touching it.
 */
Config::define('DISABLE_WP_CRON', true);

/**
 * Debugging — overridden for WP_ENV === 'development' in config/environments/.
 */
Config::define('WP_DEBUG', false);
Config::define('WP_DEBUG_DISPLAY', false);
Config::define('WP_DEBUG_LOG', false);
Config::define('SCRIPT_DEBUG', false);
ini_set('display_errors', '0');

/**
 * ALB terminates TLS; trust its forwarded-proto header so WP knows the real scheme.
 */
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
	$_SERVER['HTTPS'] = 'on';
}

$env_config = __DIR__ . '/environments/' . WP_ENV . '.php';

if (file_exists($env_config)) {
	require_once $env_config;
}

Config::apply();

/**
 * Bootstrap WordPress
 */
if (!defined('ABSPATH')) {
	define('ABSPATH', $webroot_dir . '/wp/');
}

#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

# /tmp is tmpfs and starts empty on every boot — recreate the directories
# nginx's default prefix (symlinked to /tmp/nginx-lib in the Dockerfile) needs
# before it starts, since the read-only root filesystem means it can't mkdir
# them itself.
mkdir -p /tmp/nginx-lib/logs /tmp/nginx-lib/tmp/client_body /tmp/nginx-lib/tmp/proxy \
	/tmp/nginx-lib/tmp/fastcgi /tmp/nginx-lib/tmp/uwsgi /tmp/nginx-lib/tmp/scgi

echo "Waiting for database..."
# A plain TCP reachability check, not a wp-cli command: `wp core is-installed`
# exits 1 both when the DB is unreachable and when it's reachable but WP's
# tables don't exist yet (first boot) — no reliable way to tell those apart
# from its output, so don't try. `wp db check` isn't an option either: it
# shells out to mysqlcheck, which this image doesn't have.
db_host=${DB_HOST%%:*}
db_port=${DB_HOST#*:}
if [ "$db_port" = "$db_host" ]; then
	db_port=3306
fi
for i in $(seq 1 30); do
	if php -r "exit(@fsockopen('${db_host}', ${db_port}, \$e, \$s, 2) ? 0 : 1);"; then
		break
	fi
	if [ "$i" -eq 30 ]; then
		echo "Database never became reachable, giving up." >&2
		exit 1
	fi
	sleep 2
done

if ! wp core is-installed >/dev/null 2>&1; then
	echo "First boot: installing WordPress..."
	wp core install \
		--url="${WP_HOME:-http://localhost:8080}" \
		--title="${WORDPRESS_SITE_TITLE:-Headless WordPress}" \
		--admin_user="${WORDPRESS_ADMIN_USER:-admin}" \
		--admin_password="${WORDPRESS_ADMIN_PASSWORD:?WORDPRESS_ADMIN_PASSWORD is required on first boot}" \
		--admin_email="${WORDPRESS_ADMIN_EMAIL:?WORDPRESS_ADMIN_EMAIL is required on first boot}" \
		--skip-email

	# WPGraphQL's /graphql route is a rewrite endpoint — it 404s (falls through
	# to the theme) under wp core install's default "plain" permalink structure.
	# --hard would try to write .htaccess on Apache; nginx has no such file, so
	# this only updates the rewrite_rules DB option — no filesystem write.
	wp rewrite structure '/%postname%/' --hard
fi

# No plugin-activation step: our plugins install into web/app/mu-plugins (composer.json
# installer-paths), and the Bedrock Autoloader mu-plugin (mu-plugins/bedrock-autoloader.php)
# discovers and requires them directly, bypassing the active_plugins DB row entirely —
# a better fit for ADR-0003 than an idempotent `wp plugin activate` would be.

exec "$@"

# Use Auth0's official WordPress plugin for admin authentication

`wp-login.php` is fully replaced by Auth0 SSO as the only path into `wp-admin`; there is no native WordPress login form. We use Auth0's own plugin (`auth0/wordpress`), pinned to the v5.x line (currently 5.5.0), rather than a generic OIDC plugin like miniOrange.

We initially leaned toward miniOrange because Auth0's plugin had well-documented abandonment complaints — but those all describe the old v4 release. v5.x is actively maintained (5.5.0 shipped a fix for CVE-2025-68129), so the official plugin is the better fit: no config to translate between "generic OIDC provider" and Auth0's specific model.

**Consequence**: pin the plugin version explicitly and track `auth0/wordpress` releases — don't let it silently float to a version we haven't checked.

# Immutable deploys: read-only root filesystem, no runtime plugin installs

WordPress core, plugins, and themes are baked into the Docker image at build time. The container's root filesystem is read-only at runtime. Installing or updating a plugin/theme means rebuilding and redeploying the image — the `wp-admin` plugin installer is not a supported way to change code.

This is a deliberate departure from how WordPress is normally operated (admins are used to installing plugins live from `wp-admin`), so it will look wrong to anyone unfamiliar with the setup. It follows from horizontal scaling plus stateless containers (ADR-0002): a plugin installed through one replica's filesystem wouldn't exist on the others, silently splitting behavior across the fleet. A read-only filesystem also shrinks the attack surface for an authenticated-but-compromised admin session.

**Consequence**: any content-team request for a new plugin/theme is a rebuild-and-redeploy, not a self-service action in `wp-admin`.

# Headless WordPress

A single-site WordPress installation used purely as a content backend: content is authored in `wp-admin` and exposed to the outside world only through a GraphQL API. There is no bundled frontend in this repo.

## Language

**Site**:
The one WordPress instance this repo runs — not a multisite network. There is exactly one.
_Avoid_: Network, Instance, Multisite.

**Admin**:
A person authenticated via Auth0 SSO into `wp-admin`. Admins are the only identity that can perform GraphQL mutations or use introspection.
_Avoid_: User, Editor, Author — this project doesn't distinguish WordPress's finer-grained roles at the architecture level; "Admin" means "passed Auth0 SSO."

**Consumer**:
An external application that reads content by querying the GraphQL API. Consumers are anonymous and get read-only access — this repo doesn't build or assume any particular consumer.
_Avoid_: Frontend, Client, App.

**Public API**:
The unauthenticated surface of the GraphQL endpoint: content queries only, introspection disabled, protected by query depth/complexity limits.
_Avoid_: Frontend API, Read API.

**Admin API**:
The GraphQL surface available only to an authenticated Admin: mutations and introspection.
_Avoid_: Private API, Write API.

**Media Asset**:
A file uploaded through the Media Library. Media Assets are stored in S3, never on the container's local disk.
_Avoid_: Attachment, Upload.

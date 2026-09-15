# Open GraphQL queries with depth/complexity limits, not persisted-query enforcement

The public GraphQL API accepts any query, not just a pre-registered allowlist. Protection against expensive/abusive queries comes from query depth and complexity limits (via WPGraphQL) plus per-IP rate limiting, not from persisted-query enforcement.

Persisted queries are the stronger production pattern for a public GraphQL API, and their absence here will look like an oversight to anyone who's built one before. We rejected them because this repo is backend-only (see CONTEXT.md's **Consumer**) — there's no controlled frontend to generate and ship a persisted-query manifest against. Enforcing persisted queries here would mean picking a manifest format and a build-time contract on behalf of a consumer that doesn't exist yet.

**Consequence**: if a specific consumer is later built in this org and its query set is known and stable, revisit this — persisted queries become straightforward once there's a real manifest to enforce against.

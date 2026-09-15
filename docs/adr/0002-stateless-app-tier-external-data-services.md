# Stateless application containers; database and cache live outside the container

In production, MySQL/Aurora (RDS) and Redis (ElastiCache) are external managed services, never containers running alongside WordPress. The application container holds no persistent state at all.

This follows directly from the horizontal-scaling requirement: if the database or object cache were containerized, each replica would have its own, and content/cache state would fragment across them. Bundling a database into "the WordPress container" is the common default for local/single-node setups, which is why this is worth recording — it's a deliberate departure from that default, not an oversight.

Local development is the one place a containerized MySQL + Redis (+ MinIO standing in for S3) is correct, via `docker-compose.yml` — there's only ever one replica locally, so the sharing concern doesn't apply.

**Consequence**: the application image must never assume the database or cache are reachable at `localhost`; connection info is always external config (env/secrets), even in dev.

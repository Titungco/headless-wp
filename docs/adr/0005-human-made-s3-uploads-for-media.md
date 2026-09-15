# Human Made S3-Uploads for Media Asset storage, not WP Offload Media Lite

Media Assets are stored in S3 via Human Made's `S3-Uploads` plugin, which streams uploads directly to S3 through a PHP stream wrapper with no local write. WP Offload Media Lite — the more commonly recommended default for this — was considered and rejected in favor of it.

Worth recording because Offload Media Lite is the plugin most guides point to, so picking the other one is surprising, and because a media plugin is expensive to change later (it determines how Media Asset URLs are generated and stored in the database — swapping plugins after content exists means a migration, not a config change).

**Consequence**: don't add Offload Media Lite alongside S3-Uploads "just in case" — they'll fight over the same upload hooks.

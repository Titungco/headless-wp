---
status: proposed
---

# WP-Cron disabled, no replacement scheduler yet

WordPress's built-in pseudo-cron (`DISABLE_WP_CRON` set true) is turned off in this repo, and nothing currently triggers scheduled tasks (scheduled post publishing, plugin cron hooks, etc.) in its place.

This is a known, deliberate gap, not an oversight — record it so nobody "fixes" it by just re-enabling the default pseudo-cron. Re-enabling it would be worse than the current gap: with multiple horizontally-scaled replicas, the default pseudo-cron fires on page load on *every* replica that gets traffic, causing redundant or duplicate execution of scheduled jobs. The real fix is an external trigger (e.g., EventBridge → an ECS Scheduled Task hitting `wp-cron.php` on a schedule, one execution regardless of replica count) — that's infrastructure that ships with the rest of the Terraform work, not something this repo's Docker image can provide on its own.

**Consequence**: scheduled posts and plugin cron jobs will not fire until the external trigger exists. Anyone relying on WP scheduling before then needs to run `wp cron event run --due-now` manually.

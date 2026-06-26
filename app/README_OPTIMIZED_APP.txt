Mineacle Bans optimized app directory

Upload the contents of this app directory over the existing app directory.
Keep your existing environment variables for DB_HOST, DB_NAME, DB_USERNAME, DB_PASSWORD, and any URL overrides.

Important changes:
- Smaller app directory with unused generated image/background assets removed.
- Cached bans API with short TTL for near-real-time updates without hitting MySQL on every visitor request.
- Optimized ban pagination using limit + 1 instead of COUNT(*) on every request.
- Batch player-name resolution from LiteBans history instead of repeated per-row lookups.
- Cached Minecraft status endpoint at api/server-status.php, with root server-status.php compatibility wrapper.
- Cached Discord invite endpoint.
- SEO metadata, canonical tags, Open Graph metadata, robots.txt, sitemap.xml, and JSON-LD.
- One smaller CSS file and one smaller JS file, with compatibility placeholder files for old JS paths.

Recommended env vars:
DB_HOST=your-mysql-host
DB_PORT=3306
DB_NAME=your-db
DB_USERNAME=your-user
DB_PASSWORD=your-password
DISPLAY_TIMEZONE=America/Chicago
BANS_CACHE_TTL=8
SERVER_STATUS_CACHE_TTL=25
DISCORD_CACHE_TTL=300
APP_DEBUG=false

Optional SQL indexes are in SQL_INDEXES_OPTIONAL.sql.

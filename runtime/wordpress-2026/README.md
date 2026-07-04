# WordPress 2026

Fresh non-Docker WordPress instance for the Dell remote.

## Runtime

- Root: /srv/storage/services/wordpress 2026
- Service-friendly symlink: /srv/storage/services/wordpress-2026
- URL: http://100.70.222.25:8093
- Runtime: WordPress Playground CLI, pinned in package-lock.json
- WordPress: latest, mounted into ./wordpress
- PHP: 8.3 through Playground PHP.wasm
- Database: Playground SQLite integration, stored under the mounted WordPress tree
- Docker: not used

## Commands

```bash
npm run serve
npm run serve:fresh
npm run verify
npm run inventory
```

The user-level systemd service is `wordpress-2026.service`. It runs from the symlink path so the requested folder name can retain its space.

## Codex Tooling

This project is onboarded for `wp-gutenberg-designer@personal`. Project state lives under `.wp-gutenberg-designer/` and records the plugin as the required planning and implementation tool. Do not copy or edit the global plugin inside this WordPress project.

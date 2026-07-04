# Runtime Notes

This instance avoids Docker, Apache, nginx, MySQL, and system package changes. It uses Node 24 on the Dell host plus the project-local `@wp-playground/cli` dependency.

The WordPress core files are mounted at `./wordpress` by passing `--mount-before-install=<root>/wordpress:/wordpress` to the Playground CLI. The first fresh boot downloads the current WordPress `latest` release into that directory.

The default route for live inspection is HTTP/REST at `http://100.70.222.25:8093`. For Gutenberg planning and implementation, use the local Codex `wp-gutenberg-designer@personal` plugin and keep project-specific answers, criteria, and reports in `.wp-gutenberg-designer/`.

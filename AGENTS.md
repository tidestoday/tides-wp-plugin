# AGENTS.md

## PR verification profile

- Load the global `verification-runner` skill before raising a PR. Only added/modified test files in the complete PR diff run locally; skip tests when none changed. Use this scope for routine edit-time verification too. It takes precedence over generic full-test examples in WordPress skills; preserve their other safety rules and keep full suites in existing CI.
- Language/framework: PHP WordPress plugin (PHP >=7.0), plain ES5/browser JavaScript; Composer metadata and npm tooling, no TypeScript/bundler. CI uses PHP 8.2 and Node 20.
- Lint/syntax: `npm run lint:js` checks admin.js/block.js with `node --check`; also syntax-check changed runtime.js/other JavaScript explicitly. Use `php -l <changed-php-file>` for PHP syntax in a compatible PHP environment. No PHPCS/PHPStan dependency is configured; do not claim they ran or add them solely for the PR gate. There is no `tsc`/transpilation step.
- Tests: Node's built-in runner for `tests/js/*.test.js`; run `node --test <changed-js-test> ...` with an explicit nonempty list. PHP uses the custom standalone `tests/php/run.php` harness, not PHPUnit. Run `php tests/php/run.php` only if that harness itself changed; its test cases are contained in that file. Do not run it merely because plugin code changed or invoke aggregate `npm test`/`test:js` globs.
- Execution: use the task checkout; for container PHP use `docker compose exec -T wordpress php /var/www/html/wp-content/plugins/tides-today-tides-and-weather/<file>` and confirm the mounted source. Do not bootstrap live WordPress or download WP-CLI just for isolated syntax/tests.
- Documentation/skill-only changes: `git diff --check` and instruction/command validation; application lint/compilation are not applicable. Existing CI remains unchanged.

## Commands
- `npm run lint:js` runs `node --check` on `assets/js/admin.js` and `assets/js/block.js`; it does not check `assets/js/runtime.js`.
- `npm run test:php` runs the custom PHP harness at `tests/php/run.php`; this is not PHPUnit and does not bootstrap WordPress.
- `npm run test:js` runs Node's built-in test runner over `tests/js/*.test.js`; run one JS test file with `node --test tests/js/block.test.js`.
- `npm run test` runs PHP tests first, then JS tests. CI runs PHP tests separately, then JS lint and JS tests.
- The `wordpress:latest` Docker image does not include WP-CLI. To run WP-CLI against the local Docker WordPress install, download the Phar inside the running container: `docker compose exec -T wordpress bash -lc 'curl -fsSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o /tmp/wp-cli.phar && php /tmp/wp-cli.phar --info'`.
- After downloading WP-CLI in the container, run commands like `docker compose exec -T wordpress php /tmp/wp-cli.phar plugin list --path=/var/www/html --allow-root`.
- Avoid using a separate `wordpress:cli` container for this project unless the full WordPress root is mounted; the current Compose setup only mounts this repo into `wp-content/plugins/tides-today-tides-and-weather`, so a standalone CLI container cannot see `/var/www/html` as an installed WordPress site.

## Runtime And Compatibility
- The plugin targets WordPress 5.0+ and PHP 7.0+ (`composer.json` pins platform PHP to 7.0), so avoid PHP syntax/features newer than 7.0 even though CI uses PHP 8.2.
- CI uses Node 20 for JS checks. There is no bundler or transpiler; edit browser JS directly in `assets/js/`.
- `docker-compose.yml` starts WordPress at `http://localhost:8000` and mounts this repo as `wp-content/plugins/tides-today-tides-and-weather`.
- WordPress agent skills are installed under `.opencode/skills`; load `wordpress-router` first for broad WordPress tasks, then the specific skill it recommends.

## Architecture Notes
- Plugin entrypoint is `tide-times-and-weather.php`; most behavior lives in `includes/class-tttw-plugin.php`, with the classic sidebar widget in `includes/class-tttw-widget.php`.
- Saved widget definitions are stored in the `tttw_widgets` option and normalized by `TTTW_Plugin::get_widgets()` before use.
- Front-end rendering is shared by shortcode, Gutenberg block, and classic widget via `TTTW_Plugin::render_saved_widget()`.
- The Gutenberg block is plain ES5 in `assets/js/block.js`; PHP injects `window.TTTWBlockData` before enqueueing the registered `tttw-block` script.
- Remote catalog and widget data calls go to `https://api.tidestoday.io/widgets-api/wp-v1` and are HMAC-signed with per-installation credentials stored in the `tttw_installation` option. The API base can be overridden with the `TTTW_API_BASE` constant or `tttw_api_base` filter. Calls are cached through both `wp_cache_*` and transients. Catalog TTL is 86400s; widget data TTL is 300s.
- Public widget output uses plugin-bundled `assets/js/runtime.js` plus JSON payloads from server-fetched `data.json`; visitors should not receive remotely hosted executable widget JavaScript.

## Release/Packaging Gotchas
- WordPress.org plugin metadata is in `readme.txt`; keep `Stable tag`, the plugin header version, and `TTTW_PLUGIN_VERSION` in sync when changing releases.
- `package.json` is private tooling metadata and currently does not match the plugin release version; do not treat it as the plugin version source of truth.
- `.distignore` excludes tests, Docker files, GitHub workflow files, README.md, node_modules, and large promo assets from packaged releases.

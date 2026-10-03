# AOCaptcha Design Spec

## Purpose

AOCaptcha is a public, embeddable drag-and-drop CAPTCHA: the user drags a
shape onto a matching outline. It generalizes a working reference
implementation (from a private project) into a standalone library any PHP
site can include, with a pluggable backend storage layer so it isn't tied
to PHP sessions, and is distributed both as plain drop-in files and as
Composer/npm packages.

## Scope

In scope:
- PHP library: challenge generation, drag-behavior analysis (bot
  detection heuristics), verification, pass-token issuance.
- Pluggable storage: `StorageInterface` with a session-based default
  adapter and a Redis adapter.
- Framework-agnostic HTTP handler consumers wire into their own endpoint
  file.
- JS widget (vanilla, no dependencies) + CSS, generalized from the
  reference (configurable endpoint instead of a hardcoded path).
- Composer package (`weanteomnio/aocaptcha`) and npm package
  (`@weanteomnio/aocaptcha`), plus plain drop-in files for zero-build use.
- A demo page exercising the full flow end-to-end.

Out of scope:
- Admin UI, analytics/dashboards, multi-language prompt localization
  (prompts stay in English, overridable by the consumer via config).
- Non-PHP backends (Node/Python ports) — PHP only for v1.
- Accessibility beyond what the reference already has (keyboard-only
  completion path is a known gap in the reference and is not solved here;
  noted as a follow-up, not blocking v1).

## Architecture

### PHP (`src/php/`)

- **`AOCaptcha\Challenge`** — pure logic, no I/O. Ported from
  `captcha_generate()`: picks shape/positions/rotation/size, returns a
  challenge array. Takes shape list and prompt list as constructor config
  (defaults provided) so consumers can customize without forking.
- **`AOCaptcha\BehaviorAnalyzer`** — ported from
  `captcha_analyze_behavior()` and `captcha_validate_intent()` unchanged in
  logic (path-ratio, speed coefficient of variation, direction changes,
  jitter, scoring threshold ≥6). This is the core anti-bot value of the
  library and must not be weakened during the port.
- **`AOCaptcha\Storage\StorageInterface`** — `get(string $key): ?array`,
  `set(string $key, array $value, int $ttlSeconds): void`,
  `delete(string $key): void`. Keys are opaque strings the caller
  namespaces (e.g. session id + purpose).
- **`AOCaptcha\Storage\SessionStorage`** — default adapter, stores under
  `$_SESSION`, starts a session if none active. Matches reference
  behavior exactly (challenge TTL 300s, pass TTL 600s).
- **`AOCaptcha\Storage\RedisStorage`** — constructor takes a `\Redis`
  instance (consumer wires connection details); uses `SETEX`/`GET`/`DEL`.
  Requires `ext-redis`; declared as a Composer `suggest`, not a hard
  dependency, so the core package stays dependency-free.
- **`AOCaptcha\Http\CaptchaHandler`** — framework-agnostic glue.
  Constructor takes a `StorageInterface` and an optional config array
  (shapes/prompts/tolerance). `handle(string $action, ?array $body):
  array` returns a plain array the consumer JSON-encodes (keeps the
  handler testable without faking superglobals/headers). A thin
  `handleRequest()` convenience method does the superglobal/header/echo
  wiring for the common case (reads `$_GET['action']`, raw
  `php://input`, emits the no-cache headers from the reference, echoes
  JSON, exits) for consumers who just want to drop a one-line endpoint
  file.

Session key naming: challenge and pass-token state key off the PHP
session ID already (reference behavior) — `CaptchaHandler` generates a
per-browser key from `session_id()` when using `SessionStorage`, and from
a cookie-based anonymous ID the consumer supplies when using
`RedisStorage` (documented in README; consumer is responsible for
generating/reading that ID, e.g. their own session or auth system).

### JS (`src/js/aocaptcha.js`)

Same structure and interaction model as the reference `captcha.js`
(`AoCaptcha` constructor, pointer-based drag with movement recording,
snap/verify flow, status UI). Changes from the reference:
- Endpoint URL is read from `data-ao-captcha-endpoint` on the container
  (falls back to `"aocaptcha-endpoint.php"` if unset) instead of a
  hardcoded constant — this is the only behavioral change needed to make
  it embeddable anywhere.
- Wrapped as UMD so it works both as a plain `<script>` global
  (`window.AOCaptcha`-style auto-init via `[data-ao-captcha]`, unchanged)
  and as an ES module import for bundler users.
- No other logic changes — shapes, drag physics, movement sampling, and
  verify/snap timing are ported as-is.

### CSS (`src/css/aocaptcha.css`)

Ported as-is; it already themes via custom properties on `.ao-captcha`.
Audit during implementation that no non-variable color values leaked in
(confirm against the reference before counting this done).

### Build & packaging

- `package.json`: esbuild-based build producing
  `dist/aocaptcha.umd.js`, `dist/aocaptcha.esm.js`, and a minified
  `dist/aocaptcha.min.css`. npm package name `@weanteomnio/aocaptcha`.
- `composer.json`: PSR-4 `weanteomnio\AOCaptcha\` → `src/php/`, package
  name `weanteomnio/aocaptcha`, PHP `^8.1`, `ext-redis` as `suggest`.
- Plain drop-in use: README documents copying `dist/aocaptcha.umd.js` +
  `dist/aocaptcha.min.css` + `src/php/**` directly into a project without
  Composer/npm, for zero-build embedding (matches how the reference was
  actually used).

### Demo (`demo/`)

- `demo/index.php` — a plain HTML form with `<div data-ao-captcha
  data-ao-captcha-endpoint="aocaptcha-endpoint.php">`, includes the CSS/JS,
  submits to itself and checks `$_POST['_captcha_answer']` against a
  verified pass token via `AOCaptcha\Http\CaptchaHandler`'s pass-check
  helper.
- `demo/aocaptcha-endpoint.php` — the "drop-in endpoint" example: ~10
  lines instantiating `CaptchaHandler` with `SessionStorage` and calling
  `handleRequest()`.

## Data flow (unchanged from reference, restated for the new split)

1. Widget loads → `POST {endpoint}?action=new` → `CaptchaHandler::handle`
   → `Challenge::generate()` → stored via `StorageInterface::set` → JSON
   challenge returned to widget.
2. User drags; widget records movement samples client-side.
3. On snap → `POST {endpoint}?action=verify` with movements/duration/snap
   position → `CaptchaHandler::handle` → loads stored challenge via
   `StorageInterface::get`, deletes it, runs `BehaviorAnalyzer`, and on
   success stores+returns a pass token (also via `StorageInterface`).
4. Consumer's own form-submit handler checks the pass token against
   storage (one-time use, like the reference's `captcha_verify()`) before
   trusting the submission.

## Error handling

- Expired/missing challenge, failed behavior analysis, malformed request
  body → same JSON error shapes as the reference
  (`{valid:false,message:...}`), so the ported JS widget needs no changes
  to its error-handling paths.
- `RedisStorage` surfaces connection errors by throwing — the library
  does not silently fall back to another adapter; that's a consumer
  configuration error, not a runtime condition to paper over.

## Testing

- PHP: PHPUnit tests for `BehaviorAnalyzer` (fixture movement arrays
  representing bot-like linear motion vs. human-like motion, asserting
  the ≥6 score threshold behavior is preserved from the reference) and
  for `SessionStorage`/`RedisStorage` (set/get/delete/TTL expiry,
  `RedisStorage` test uses a fake/in-memory Redis test double).
- JS: no automated test for the drag interaction itself (not easily
  unit-tested, consistent with the reference); verified manually via the
  demo page during implementation.

## Open follow-ups (not blocking v1)

- Keyboard-accessible completion path (reference doesn't have one
  either).
- Rate limiting / abuse throttling on the endpoint is left to the
  consumer (e.g. their own reverse proxy or middleware) — not part of
  this library.

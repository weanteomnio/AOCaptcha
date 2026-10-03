# AOCaptcha

An embeddable drag-and-drop CAPTCHA: the visitor drags a shape onto its
matching outline. Server-side verification scores the drag's movement
pattern (speed variance, path curvature, direction changes) in addition
to the final drop position, so a scripted straight-line drop is rejected
even if it lands in the right place.

## Install

**Composer (PHP):**

```bash
composer require weanteomnio/aocaptcha
```

**npm (JS/CSS):**

```bash
npm install @weanteomnio/aocaptcha
```

**Or, drop-in (no build tools):** copy `src/js/aocaptcha.js`,
`src/css/aocaptcha.css`, and `src/php/**` directly into your project.

## Usage

1. Create an endpoint file your frontend will call:

```php
<?php
require 'vendor/autoload.php';

use weanteomnio\AOCaptcha\Http\CaptchaHandler;
use weanteomnio\AOCaptcha\Storage\SessionStorage;

(new CaptchaHandler(new SessionStorage()))->handleRequest();
```

2. In your form's HTML, add a container and point it at that endpoint:

```html
<link rel="stylesheet" href="/vendor/weanteomnio/aocaptcha/dist/aocaptcha.min.css">

<form method="post">
  <div data-ao-captcha data-ao-captcha-endpoint="/captcha-endpoint.php"></div>
  <button type="submit">Submit</button>
</form>

<script src="/vendor/weanteomnio/aocaptcha/dist/aocaptcha.umd.js"></script>
```

The widget appends a hidden `_captcha_answer` input to the form once
solved; your form won't submit until it's verified client-side.

3. On your form's own submit handler, check the token server-side:

```php
<?php
use weanteomnio\AOCaptcha\Http\CaptchaHandler;
use weanteomnio\AOCaptcha\Storage\SessionStorage;

$handler = new CaptchaHandler(new SessionStorage());
if (!$handler->verifyPass($_POST['_captcha_answer'] ?? '')) {
    // reject the submission
}
```

See `demo/` for a complete, runnable example
(`php -S localhost:8080 -t demo demo/router.php`, from the repo root —
the router lets the dev server expose the sibling `dist/` build output
that the demo page links to).

## Storage adapters

- `weanteomnio\AOCaptcha\Storage\SessionStorage` — default, uses
  `$_SESSION`. No extra setup.
- `weanteomnio\AOCaptcha\Storage\RedisStorage` — for stateless/multi-server
  deployments. Requires `ext-redis`. Pass a connected `\Redis` instance:

```php
$redis = new Redis();
$redis->connect('127.0.0.1');
$handler = new CaptchaHandler(new RedisStorage($redis));
```

When using `RedisStorage`, pass an explicit `$browserId` (e.g. your own
session or auth identifier) as the third argument to `handle()` and
`verifyPass()`, since there's no PHP session to derive one from
automatically.

## License

MIT

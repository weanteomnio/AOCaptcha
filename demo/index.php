<?php

require __DIR__ . '/../vendor/autoload.php';

use weanteomnio\AOCaptcha\Http\CaptchaHandler;
use weanteomnio\AOCaptcha\Storage\SessionStorage;

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $handler = new CaptchaHandler(new SessionStorage());
    $token = (string) ($_POST['_captcha_answer'] ?? '');
    $message = $handler->verifyPass($token)
        ? 'Form submitted — CAPTCHA verified.'
        : 'CAPTCHA verification failed. Please try again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>AOCaptcha demo</title>
  <link rel="stylesheet" href="../dist/aocaptcha.min.css">
</head>
<body>
  <h1>AOCaptcha demo</h1>
  <?php if ($message !== ''): ?>
    <p><?= htmlspecialchars($message) ?></p>
  <?php endif; ?>
  <form method="post">
    <div data-ao-captcha data-ao-captcha-endpoint="aocaptcha-endpoint.php"></div>
    <button type="submit">Submit</button>
  </form>
  <script src="../dist/aocaptcha.umd.js"></script>
</body>
</html>

<?php

require __DIR__ . '/../vendor/autoload.php';

use weanteomnio\AOCaptcha\Http\CaptchaHandler;
use weanteomnio\AOCaptcha\Storage\SessionStorage;

$handler = new CaptchaHandler(new SessionStorage());
$handler->handleRequest();

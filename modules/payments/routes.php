<?php

use Slim\App;

$app->get('/wire', \Modules\payments\Views\Payments::class);
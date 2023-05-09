<?php

use Slim\App;

$app->get('/privacy', \Modules\privacy_policy\Views\Privacy::class);
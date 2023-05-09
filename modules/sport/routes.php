<?php

use Slim\App;

$app->get('/sport', \Modules\sport\Sport::class);
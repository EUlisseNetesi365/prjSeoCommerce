<?php

use Slim\App;

$app->post('/search', \Modules\search\Controllers\SearchProducts::class);
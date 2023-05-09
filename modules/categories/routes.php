<?php

use Slim\App;

$app->get('/category/{id}', \Modules\categories\Views\Categories::class);
$app->post('/page', \Modules\categories\Controllers\Page::class);
$app->post('/filter-items', \Modules\categories\Controllers\Filter::class);
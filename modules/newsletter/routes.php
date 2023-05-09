<?php

use Slim\App;

$app->post('/newsletter', \Modules\newsletter\Controllers\AddNewsletter::class);
$app->get('/news_confirm/{code}', \Modules\newsletter\Views\ConfirmNews::class);

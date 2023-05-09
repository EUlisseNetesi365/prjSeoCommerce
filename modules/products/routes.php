<?php

use Slim\App;

$app->get('/product/{id}', \Modules\products\Views\Product::class);
$app->post('/get-vid', \Modules\products\Controllers\VariantAttributePrimary::class);


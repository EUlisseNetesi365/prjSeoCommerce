<?php

use Slim\App;

$app->get('/catalogo', \Modules\catalogo\Views\Catalogo::class);
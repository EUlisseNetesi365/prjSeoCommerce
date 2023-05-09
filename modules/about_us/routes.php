<?php

use Slim\App;

$app->get('/about_us', \Modules\about_us\Views\About::class);
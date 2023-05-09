<?php

use Slim\App;

$app->get('/contact_us', \Modules\contact_us\Views\ContactUs::class);
$app->post('/info_rq', \Modules\contact_us\Controllers\SendRequestInfo::class);

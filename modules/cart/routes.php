<?php

use Slim\App;

$app->get('/cart', \Modules\cart\Views\CartCheckout::class);
$app->post('/add_to_cart', \Modules\cart\Controllers\AddToCart::class);
$app->post('/edit_item_cart', \Modules\cart\Controllers\EditItemCart::class);
$app->post('/del_from_cart', \Modules\cart\Controllers\DelFromCart::class);
$app->post('/view_cart', \Modules\cart\Controllers\ViewCart::class);
$app->post('/add_customer_info', \Modules\cart\Controllers\AddCustomerInfo::class);
$app->post('/confirm_order', \Modules\cart\Controllers\ConfirmOrder::class);

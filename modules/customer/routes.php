<?php

use Modules\customer\MiddleWare\IsLogged;
use Modules\customer\MiddleWare\DenyNotLogged;
use Modules\customer\MiddleWare\DenyWithoutValidCode;

/**
 * Customer Login Registration route
 */
$app->post('/login', \Modules\customer\Controllers\LoginController::class);
$app->post('/resend_em', \Modules\customer\Controllers\ResendConfirm::class);
$app->post('/create_c', \Modules\customer\Controllers\AddCustomer::class);
$app->get('/logout', \Modules\customer\Controllers\LogoutController::class);
$app->get('/confirmcode/{code}', \Modules\customer\Views\ConfirmEmail::class);
$app->get('/profile', \Modules\customer\Views\Profile::class)->add(DenyNotLogged::class);


/**
 * Customer Data route
 */
$app->post('/read_cdata', \Modules\customer\Controllers\ReadCustomerData::class);
$app->post('/create_cdata', \Modules\customer\Controllers\CreateCustomerData::class);
$app->post('/update_cdata', \Modules\customer\Controllers\UpdateCustomerData::class);
$app->post('/delete_cdata', \Modules\customer\Controllers\DeleteCustomerData::class);

/**
 * Customer Logged Address Data route
 */
$app->post('/update_address_cart', \Modules\customer\Controllers\CustomerLoggedAddress::class);

/**
 * Customer Additional notice route
 */
$app->post('/read_em', \Modules\customer\Controllers\ReadAdditionalMails::class);
$app->post('/create_em', \Modules\customer\Controllers\CreateAdditionalMail::class);
$app->post('/update_em', \Modules\customer\Controllers\UpdateLoginMail::class);

/**
 * Customer Change and recover password route
 */
$app->post('/change_pwd', \Modules\customer\Controllers\UpdateCustomerPassword::class);
$app->post('/recover', \Modules\customer\Controllers\RecoverCustomerPassword::class);
$app->get('/recover_confirm/{code}', \Modules\customer\Views\RecoverPassword::class)->add(DenyWithoutValidCode::class);






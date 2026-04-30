<?php

use CodeIgniter\Router\RouteCollection;

$routes->get('/', 'Home::index');

$routes->group('api', ['namespace' => 'App\Controllers'], function (RouteCollection $routes) {
    $routes->post('auth/register', 'Auth::register');
    $routes->post('auth/login', 'Auth::login');
    $routes->get('auth/profile', 'Auth::profile', ['filter' => 'auth:tenant']);
    $routes->post('auth/change-password', 'Auth::changePassword', ['filter' => 'auth:tenant']);
    $routes->post('auth/logout', 'Auth::logout', ['filter' => 'auth:tenant']);
    $routes->post('auth/refresh', 'Auth::refreshToken', ['filter' => 'auth:tenant']);

    $routes->group('', ['filter' => ['auth:tenant', 'rateLimit']], function (RouteCollection $routes) {
        $routes->get('tenant', 'Tenant::index');
        $routes->put('tenant', 'Tenant::update');
        $routes->get('tenant/usage', 'Tenant::usage');
        $routes->post('tenant/upload-logo', 'Tenant::uploadLogo');

        $routes->get('users', 'User::index');
        $routes->post('users', 'User::create');
        $routes->get('users/(:num)', 'User::show/$1');
        $routes->put('users/(:num)', 'User::update/$1');
        $routes->put('users/(:num)/status', 'User::updateStatus/$1');
        $routes->put('users/(:num)/reset-password', 'User::resetPassword/$1');
        $routes->delete('users/(:num)', 'User::delete/$1');

        $routes->get('products', 'Product::index');
        $routes->get('products/categories', 'Product::categories');
        $routes->post('products', 'Product::create');
        $routes->get('products/(:num)', 'Product::show/$1');
        $routes->put('products/(:num)', 'Product::update/$1');
        $routes->delete('products/(:num)', 'Product::delete/$1');

        $routes->post('upload/image', 'Product::uploadImage');

        $routes->get('batches', 'Batch::index');
        $routes->post('batches', 'Batch::create');
        $routes->get('batches/(:num)', 'Batch::show/$1');
        $routes->put('batches/(:num)', 'Batch::update/$1');
        $routes->delete('batches/(:num)', 'Batch::delete/$1');

        $routes->get('batches/(:num)/trace-records', 'TraceRecord::byBatch/$1');
        $routes->post('batches/(:num)/trace-records', 'TraceRecord::create/$1');
        $routes->put('trace-records/(:num)', 'TraceRecord::update/$1');
        $routes->delete('trace-records/(:num)', 'TraceRecord::delete/$1');

        $routes->get('qrcodes', 'Qrcode::index');
        $routes->post('qrcodes/generate', 'Qrcode::generate');
        $routes->get('qrcodes/(:num)', 'Qrcode::show/$1');
        $routes->put('qrcodes/(:num)/status', 'Qrcode::updateStatus/$1');
        $routes->put('qrcodes/(:num)/disable', 'Qrcode::disable/$1');
        $routes->get('qrcodes/batch/(:num)', 'Qrcode::byBatch/$1');
        $routes->post('qrcodes/print', 'Qrcode::print');
        $routes->get('qrcodes/download/(:num)', 'Qrcode::download/$1');

        $routes->get('audit', 'AuditLog::index');
        $routes->get('audit/(:num)', 'AuditLog::show/$1');
        $routes->get('audit/resource/(:segment)/(:segment)', 'AuditLog::resource/$1/$2');
        $routes->get('audit/logs/export', 'AuditLog::export');

        $routes->get('statistics', 'Statistics::index');
        $routes->get('statistics/scan', 'Statistics::scan');
        $routes->get('statistics/product', 'Statistics::product');
        $routes->get('statistics/risk', 'Statistics::risk');

        $routes->get('billing', 'Billing::index');
        $routes->get('billing/plans', 'Billing::plans');
        $routes->get('billing/current', 'Billing::current');
        $routes->post('billing/upgrade', 'Billing::upgrade');
        $routes->get('billing/check-expiration', 'Billing::checkExpiration');
    });

    $routes->get('scan/verify/(:segment)', 'Scan::verify/$1');
    $routes->get('scan/history/(:num)', 'Scan::history/$1');
});

$routes->get('verify/(:segment)', 'Scan::verify/$1');

<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Storefront
$routes->get('/', 'Home::index', ['as' => 'home']);
$routes->get('shop', 'Shop::index', ['as' => 'shop']);
$routes->get('shop/(:segment)', 'Shop::category/$1', ['as' => 'shop.category']);
$routes->get('products/(:segment)', 'Products::show/$1', ['as' => 'products.show']);
$routes->get('bespoke', 'Pages::bespoke', ['as' => 'bespoke']);
$routes->post('bespoke/(custom|bridal)', 'Pages::enquire/$1', ['as' => 'bespoke.enquire', 'filter' => 'throttle:10']);
$routes->get('saree-draping', 'Draping::index', ['as' => 'draping']);
$routes->post('saree-draping', 'Draping::book', ['as' => 'draping.book', 'filter' => 'throttle:10']);

// Cart
$routes->get('cart', 'Cart::index', ['as' => 'cart']);
$routes->post('cart/add', 'Cart::add', ['as' => 'cart.add']);
$routes->post('cart/update/(:num)', 'Cart::update/$1', ['as' => 'cart.update']);
$routes->post('cart/remove/(:num)', 'Cart::remove/$1', ['as' => 'cart.remove']);
$routes->post('cart/voucher', 'Cart::applyVoucher', ['as' => 'cart.voucher', 'filter' => 'throttle:10']);
$routes->post('cart/voucher/remove', 'Cart::removeVoucher', ['as' => 'cart.voucher.remove']);

// Checkout
$routes->get('checkout', 'Checkout::index', ['as' => 'checkout']);
$routes->post('checkout', 'Checkout::place', ['as' => 'checkout.place', 'filter' => 'throttle:10']);
$routes->get('orders/(:segment)', 'Orders::show/$1', ['as' => 'orders.show']);

// Website chat
$routes->post('chat', 'Chat::reply', ['as' => 'chat', 'filter' => 'throttle:30']);

// WhatsApp bot. Only Meta calls these, and the controller checks that it did.
$routes->get('webhooks/whatsapp', 'WhatsAppWebhook::verify');
$routes->post('webhooks/whatsapp', 'WhatsAppWebhook::receive');

// Sign up, sign in and sign out, for customers and admins alike. Magic links stay
// off because they need an email service.
service('auth')->routes($routes, ['except' => ['magic-link']]);
$routes->get('account', 'Account::index', ['as' => 'account', 'filter' => 'session']);
$routes->get('auth/google', 'GoogleSignIn::start', ['as' => 'auth.google']);
$routes->get('auth/google/callback', 'GoogleSignIn::callback', ['as' => 'auth.google.callback']);

// Admin
$routes->group('admin', [
    'namespace' => 'App\Controllers\Admin',
    'filter'    => ['session', 'group:superadmin,admin'],
], static function ($routes) {
    $routes->get('/', 'Dashboard::index', ['as' => 'admin.home']);

    $routes->get('products', 'Products::index', ['as' => 'admin.products']);
    $routes->get('products/new', 'Products::new', ['as' => 'admin.products.new']);
    $routes->post('products', 'Products::create', ['as' => 'admin.products.create']);
    $routes->get('products/(:num)/edit', 'Products::edit/$1', ['as' => 'admin.products.edit']);
    $routes->post('products/(:num)', 'Products::update/$1', ['as' => 'admin.products.update']);
    $routes->post('products/(:num)/delete', 'Products::delete/$1', ['as' => 'admin.products.delete']);

    $routes->get('categories', 'Categories::index', ['as' => 'admin.categories']);
    $routes->post('categories', 'Categories::create', ['as' => 'admin.categories.create']);
    $routes->get('categories/(:num)/edit', 'Categories::edit/$1', ['as' => 'admin.categories.edit']);
    $routes->post('categories/(:num)', 'Categories::update/$1', ['as' => 'admin.categories.update']);
    $routes->post('categories/(:num)/delete', 'Categories::delete/$1', ['as' => 'admin.categories.delete']);

    $routes->get('options', 'Options::index', ['as' => 'admin.options']);
    $routes->post('options/(colours|sizes)', 'Options::create/$1', ['as' => 'admin.options.create']);
    $routes->post('options/(colours|sizes)/(:num)', 'Options::update/$1/$2', ['as' => 'admin.options.update']);
    $routes->post('options/(colours|sizes)/(:num)/delete', 'Options::delete/$1/$2', ['as' => 'admin.options.delete']);

    $routes->get('orders', 'Orders::index', ['as' => 'admin.orders']);
    $routes->get('orders/(:num)', 'Orders::show/$1', ['as' => 'admin.orders.show']);
    $routes->post('orders/(:num)/status', 'Orders::updateStatus/$1', ['as' => 'admin.orders.status']);
    $routes->post('orders/(:num)/source', 'Orders::source/$1', ['as' => 'admin.orders.source']);

    $routes->get('bookings', 'Bookings::index', ['as' => 'admin.bookings']);
    $routes->get('bookings/(:num)', 'Bookings::show/$1', ['as' => 'admin.bookings.show']);
    $routes->post('bookings/(:num)/status', 'Bookings::updateStatus/$1', ['as' => 'admin.bookings.status']);

    $routes->get('offers', 'Offers::index', ['as' => 'admin.offers']);
    $routes->get('offers/new', 'Offers::new', ['as' => 'admin.offers.new']);
    $routes->post('offers', 'Offers::create', ['as' => 'admin.offers.create']);
    $routes->get('offers/(:num)/edit', 'Offers::edit/$1', ['as' => 'admin.offers.edit']);
    $routes->post('offers/(:num)', 'Offers::update/$1', ['as' => 'admin.offers.update']);
    $routes->post('offers/(:num)/delete', 'Offers::delete/$1', ['as' => 'admin.offers.delete']);

    $routes->get('vouchers', 'Vouchers::index', ['as' => 'admin.vouchers']);
    $routes->post('vouchers', 'Vouchers::generate', ['as' => 'admin.vouchers.generate']);
    $routes->get('vouchers/export', 'Vouchers::export', ['as' => 'admin.vouchers.export']);
    $routes->post('vouchers/(:num)/toggle', 'Vouchers::toggle/$1', ['as' => 'admin.vouchers.toggle']);

    $routes->get('enquiries', 'Enquiries::index', ['as' => 'admin.enquiries']);
    $routes->get('enquiries/(:num)', 'Enquiries::show/$1', ['as' => 'admin.enquiries.show']);
    $routes->post('enquiries/(:num)/status', 'Enquiries::updateStatus/$1', ['as' => 'admin.enquiries.status']);
    $routes->post('enquiries/(:num)/sale', 'Enquiries::sale/$1', ['as' => 'admin.enquiries.sale']);

    $routes->get('analytics', 'Analytics::index', ['as' => 'admin.analytics']);
    $routes->post('analytics/enquiries', 'Analytics::logEnquiry', ['as' => 'admin.analytics.enquiries']);
    $routes->post('analytics/posts', 'Analytics::logPost', ['as' => 'admin.analytics.posts']);
    $routes->post('analytics/posts/(:num)/delete', 'Analytics::deletePost/$1', ['as' => 'admin.analytics.posts.delete']);
});

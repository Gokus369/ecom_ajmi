<?php

namespace Config;

use CodeIgniter\Config\BaseService;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    public static function cart(bool $getShared = true): \App\Libraries\Cart
    {
        if ($getShared) {
            return static::getSharedInstance('cart');
        }

        return new \App\Libraries\Cart(
            model(\App\Models\ProductModel::class),
            static::session(),
            config(Store::class),
            model(\App\Models\OfferModel::class),
            model(\App\Models\VoucherModel::class),
        );
    }

    public static function orders(bool $getShared = true): \App\Libraries\OrderService
    {
        if ($getShared) {
            return static::getSharedInstance('orders');
        }

        return new \App\Libraries\OrderService(
            model(\App\Models\OrderModel::class),
            model(\App\Models\OrderItemModel::class),
            model(\App\Models\ProductModel::class),
            model(\App\Models\VoucherModel::class),
        );
    }

    public static function productImages(bool $getShared = true): \App\Libraries\ProductImages
    {
        if ($getShared) {
            return static::getSharedInstance('productImages');
        }

        return new \App\Libraries\ProductImages();
    }

    public static function google(bool $getShared = true): \App\Libraries\GoogleClient
    {
        if ($getShared) {
            return static::getSharedInstance('google');
        }

        return new \App\Libraries\GoogleClient();
    }

    public static function whatsapp(bool $getShared = true): \App\Libraries\WhatsAppClient
    {
        if ($getShared) {
            return static::getSharedInstance('whatsapp');
        }

        return new \App\Libraries\WhatsAppClient();
    }
}

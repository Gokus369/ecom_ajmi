<?php

namespace App\Controllers;

use App\Models\OrderModel;

class Account extends BaseController
{
    private const MAX_ORDERS = 50;

    public function index(): string
    {
        $user = auth()->user();

        return view('account/index', [
            'title'   => 'Your account',
            'noindex' => true,
            'user'    => $user,
            'orders'  => model(OrderModel::class)->where('user_id', $user->id)->newest()->findAll(self::MAX_ORDERS),
        ]);
    }
}

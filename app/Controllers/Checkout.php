<?php

namespace App\Controllers;

use App\Exceptions\OutOfStock;
use App\Exceptions\VoucherUnavailable;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\HTTP\RedirectResponse;

class Checkout extends BaseController
{
    public function index()
    {
        $cart  = service('cart');
        $lines = $cart->lines();

        if ($lines === []) {
            return redirect()->route('cart');
        }

        return view('checkout/index', [
            'title'   => 'Checkout',
            'noindex' => true,
            'lines'   => $lines,
            'totals'  => $cart->totals($lines),
            'notices' => $cart->notices(),
        ]);
    }

    public function place(): RedirectResponse
    {
        $cart  = service('cart');
        $lines = $cart->lines();

        if ($lines === []) {
            // A double click lands here: the first click placed the order and emptied the cart.
            $lastOrder = session('last_order');

            return $lastOrder
                ? redirect()->route('orders.show', [$lastOrder])
                : redirect()->route('cart');
        }

        $input = array_map(static fn ($value) => is_string($value) ? trim($value) : $value, $this->request->getPost());

        if (! $this->validateData($input, 'checkout')) {
            return redirect()->route('checkout')->withInput()->with('errors', $this->validator->getErrors());
        }

        // Never charge a total the shopper did not see.
        $totals = $cart->totals($lines);

        if ($cart->notices() !== [] || (int) ($input['expected_total'] ?? 0) !== $totals['total']) {
            return redirect()->route('checkout')->withInput()
                ->with('warning', 'Your order changed while you were checking out. Please review it and place it again.');
        }

        $customer = $this->validator->getValidated();

        $customer['email'] = ($customer['email'] ?? '') ?: null;
        $customer['notes'] = ($customer['notes'] ?? '') ?: null;

        // Null for a guest. Only ever taken from the session, never from the form.
        $customer['user_id'] = auth()->id();

        try {
            $order = service('orders')->place($customer, $lines, $totals);
        } catch (OutOfStock $e) {
            return redirect()->route('cart')->with('error', "Sorry, {$e->productName} just sold out. Your order was not placed.");
        } catch (VoucherUnavailable) {
            $cart->removeVoucher();

            return redirect()->route('checkout')->withInput()
                ->with('warning', 'That voucher has just been used up, so we removed it. Please review your order and place it again.');
        } catch (DatabaseException $e) {
            log_message('error', 'Checkout failed: {message}', ['message' => $e->getMessage()]);

            return redirect()->route('checkout')->withInput()
                ->with('error', 'Something went wrong on our side and your order was not placed. Please try again.');
        }

        $cart->clear();
        session()->set('last_order', $order->number);

        return redirect()->route('orders.show', [$order->number])->with('placed', true);
    }
}

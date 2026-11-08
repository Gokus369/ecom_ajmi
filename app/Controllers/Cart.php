<?php

namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\VoucherModel;
use CodeIgniter\HTTP\RedirectResponse;

class Cart extends BaseController
{
    private const MAX_PER_ITEM = 99;

    public function index(): string
    {
        $cart  = service('cart');
        $lines = $cart->lines();

        return view('cart/index', [
            'title'   => 'Your cart',
            'lines'       => $lines,
            'totals'      => $cart->totals($lines),
            'notices'     => $cart->notices(),
            'voucher'     => $cart->voucherCode(),
            'voucherNote' => $cart->voucherNote(),
        ]);
    }

    public function applyVoucher(): RedirectResponse
    {
        $code    = $this->request->getPost('code');
        $voucher = is_string($code) ? model(VoucherModel::class)->findUsable($code) : null;

        // One message for every failure, so a guess learns nothing about real codes.
        if ($voucher === null) {
            return redirect()->route('cart')->with('error', 'That voucher code is not valid.');
        }

        service('cart')->setVoucher($voucher->code);

        return redirect()->route('cart')->with('message', "Voucher {$voucher->code} added.");
    }

    public function removeVoucher(): RedirectResponse
    {
        service('cart')->removeVoucher();

        return redirect()->route('cart');
    }

    public function add(): RedirectResponse
    {
        $data  = $this->request->getPost(['product_id', 'quantity']);
        $rules = [
            'product_id' => 'required|is_natural_no_zero',
            'quantity'   => 'permit_empty|is_natural_no_zero|less_than_equal_to[' . self::MAX_PER_ITEM . ']',
        ];

        if (! $this->validateData($data, $rules)) {
            return redirect()->back()->with('error', 'Please choose a quantity between 1 and ' . self::MAX_PER_ITEM . '.');
        }

        $product = model(ProductModel::class)->published()->find((int) $data['product_id']);

        if ($product === null) {
            return redirect()->back()->with('error', 'Sorry, that product is no longer available.');
        }

        if ($product->is_enquiry_only) {
            return redirect()->back()->with('error', "{$product->name} is made to order. Please message us about it.");
        }

        if (! $product->isInStock()) {
            return redirect()->back()->with('error', "Sorry, {$product->name} is sold out.");
        }

        [$colour, $size] = $this->chosenOptions();

        if (! $product->offers($colour, $size)) {
            return redirect()->back()->with('error', "Please choose your options for {$product->name}.");
        }

        $cart  = service('cart');
        $left  = $product->stockOf($colour, $size);
        $label = $cart->label($product, $colour, $size);

        if ($left < 1) {
            return redirect()->back()->with('error', "Sorry, {$label} is sold out.");
        }

        $wanted = $cart->quantityOf($product->id, $colour, $size) + (int) ($data['quantity'] ?: 1);

        $cart->set($product, min($wanted, self::MAX_PER_ITEM), $colour, $size);

        if ($wanted > $left) {
            return redirect()->back()->with('warning', "We only have {$left} of {$label}, and they are all in your cart.");
        }

        return redirect()->back()->with('message', "{$product->name} is in your cart.");
    }

    public function update(int $productId): RedirectResponse
    {
        $cart     = service('cart');
        $quantity = (int) $this->request->getPost('quantity');

        [$colour, $size] = $this->chosenOptions();

        if ($cart->quantityOf($productId, $colour, $size) === 0) {
            return redirect()->route('cart');
        }

        $product = model(ProductModel::class)->published()->find($productId);

        if ($product === null || $quantity < 1) {
            $cart->remove($productId, $colour, $size);

            return redirect()->route('cart');
        }

        $wanted = min($quantity, self::MAX_PER_ITEM);

        $cart->set($product, $wanted, $colour, $size);

        if ($wanted > $cart->quantityOf($productId, $colour, $size)) {
            return redirect()->route('cart')->with('warning', "We only have {$product->stockOf($colour, $size)} of {$product->name} right now.");
        }

        return redirect()->route('cart');
    }

    public function remove(int $productId): RedirectResponse
    {
        service('cart')->remove($productId, ...$this->chosenOptions());

        return redirect()->route('cart');
    }

    /**
     * @return array{0: string, 1: string} the colour and size posted, empty when there is none
     */
    private function chosenOptions(): array
    {
        return array_map(function (string $field): string {
            $value = $this->request->getPost($field);

            return is_string($value) ? trim($value) : '';
        }, ['colour', 'size']);
    }
}

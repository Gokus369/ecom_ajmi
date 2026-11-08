<?php

namespace App\Libraries;

use App\Entities\Product;
use App\Models\OfferModel;
use App\Models\ProductModel;
use App\Models\VoucherModel;
use CodeIgniter\Session\SessionInterface;
use Config\Store;

/**
 * The shopper's cart. The session holds only lines and quantities; names,
 * prices and stock are read fresh so a cart can never go stale.
 *
 * Each colour and size of a product is its own line, measured against that
 * combination's own stock.
 */
class Cart
{
    private const SESSION_KEY = 'cart';
    private const VOUCHER_KEY = 'voucher';

    /** A control character, so no colour or size can contain it. */
    private const KEY_SEPARATOR = "\x1F";

    /** @var list<string> */
    private array $notices = [];

    /** Why the entered voucher is not taking anything off, from the last call to totals(). */
    private ?string $voucherNote = null;

    public function __construct(
        private readonly ProductModel $products,
        private readonly SessionInterface $session,
        private readonly Store $store,
        private readonly OfferModel $offers,
        private readonly VoucherModel $vouchers,
    ) {
    }

    /**
     * @return array<int|string, int> quantity keyed by line, see key()
     */
    public function quantities(): array
    {
        return (array) $this->session->get(self::SESSION_KEY);
    }

    /**
     * Identifies a line. A product with no colour or size to choose is just its ID.
     */
    public function key(int $productId, string $colour = '', string $size = ''): int|string
    {
        return $colour === '' && $size === '' ? $productId : implode(self::KEY_SEPARATOR, [$productId, $colour, $size]);
    }

    public function quantityOf(int $productId, string $colour = '', string $size = ''): int
    {
        return $this->quantities()[$this->key($productId, $colour, $size)] ?? 0;
    }

    /** Total units, for the header badge. */
    public function count(): int
    {
        return array_sum($this->quantities());
    }

    /**
     * Sets a line's quantity, capped at what that choice has left.
     */
    public function set(Product $product, int $quantity, string $colour = '', string $size = ''): void
    {
        $items = $this->quantities();

        $items[$this->key($product->id, $colour, $size)] = min($quantity, $product->stockOf($colour, $size));

        $this->save($items);
    }

    public function remove(int $productId, string $colour = '', string $size = ''): void
    {
        $items = $this->quantities();

        unset($items[$this->key($productId, $colour, $size)]);

        $this->save($items);
    }

    public function clear(): void
    {
        $this->session->remove(self::SESSION_KEY);
        $this->removeVoucher();
    }

    public function setVoucher(string $code): void
    {
        $this->session->set(self::VOUCHER_KEY, $code);
    }

    public function removeVoucher(): void
    {
        $this->session->remove(self::VOUCHER_KEY);
    }

    public function voucherCode(): ?string
    {
        return $this->session->get(self::VOUCHER_KEY);
    }

    public function voucherNote(): ?string
    {
        return $this->voucherNote;
    }

    /**
     * The cart as it stands right now: products that were unpublished or sold
     * out drop off, as do choices the product no longer offers, quantities
     * shrink to fit stock, and notices() says what changed.
     *
     * @return list<array{product: Product, variant_id: int|null, colour: string, size: string, quantity: int, total: int, more: bool}>
     */
    public function lines(): array
    {
        $this->notices = [];

        $items = $this->quantities();

        if ($items === []) {
            return [];
        }

        $ids = array_unique(array_map(fn (int|string $key): int => $this->parts($key)[0], array_keys($items)));

        $found = $this->products->published()
            ->where('is_enquiry_only', 0)
            ->whereIn('id', $ids)
            ->findAll();

        if (count($found) < count($ids)) {
            $this->notices[] = 'An item in your cart is no longer available, so we removed it.';
        }

        $products = [];

        foreach ($found as $product) {
            $products[$product->id] = $product;
        }

        $lines = [];
        $kept  = [];

        foreach ($items as $key => $wanted) {
            [$productId, $colour, $size] = $this->parts($key);

            if (! isset($products[$productId])) {
                continue;
            }

            $product = $products[$productId];
            $label   = $this->label($product, $colour, $size);

            if (! $product->offers($colour, $size)) {
                $this->notices[] = "{$label} has changed, so we removed it from your cart. Please choose it again.";

                continue;
            }

            $left     = $product->stockOf($colour, $size);
            $quantity = min($wanted, $left);

            if ($quantity < 1) {
                $this->notices[] = "{$label} just sold out, so we removed it from your cart.";

                continue;
            }

            if ($quantity < $wanted) {
                $this->notices[] = "Only {$quantity} of {$label} left, so we updated your cart.";
            }

            $kept[$key] = $quantity;

            $lines[] = [
                'product'    => $product,
                'variant_id' => $product->variantFor($colour, $size)?->id,
                'colour'     => $colour,
                'size'       => $size,
                'quantity'   => $quantity,
                'total'      => $quantity * $product->price,
                'more'       => $quantity < $left,
            ];
        }

        $this->save($kept);

        return $lines;
    }

    /**
     * @return list<string> what the last call to lines() had to change
     */
    public function notices(): array
    {
        return $this->notices;
    }

    /**
     * @param list<array{total: int}> $lines
     *
     * @return array{subtotal: int, discount: int, discount_label: string|null, voucher_id: int|null, shipping: int, total: int}
     */
    public function totals(array $lines): array
    {
        $subtotal = array_sum(array_column($lines, 'total'));
        $discount = $this->bestDiscount($subtotal);
        $shipping = $this->shippingFor($subtotal - $discount['discount']);

        return [
            'subtotal' => $subtotal,
            ...$discount,
            'shipping' => $shipping,
            'total'    => $subtotal - $discount['discount'] + $shipping,
        ];
    }

    /**
     * The better of the running offer and the shopper's voucher. They never add
     * together, and a voucher that does not win is left unspent.
     *
     * @return array{discount: int, discount_label: string|null, voucher_id: int|null}
     */
    private function bestDiscount(int $subtotal): array
    {
        $this->voucherNote = null;

        $offer  = $this->offers->best($subtotal, store_today());
        $amount = $offer?->amountOff($subtotal) ?? 0;
        $best   = ['discount' => $amount, 'discount_label' => $offer?->name, 'voucher_id' => null];
        $code   = $this->voucherCode();

        if ($code === null) {
            return $best;
        }

        $voucher = $this->vouchers->findUsable($code);

        if ($voucher === null) {
            $this->removeVoucher();
            $this->notices[] = "Voucher {$code} is no longer valid, so we removed it.";

            return $best;
        }

        $off = $voucher->amountOff($subtotal);

        if ($off === 0) {
            $this->voucherNote = 'Add ' . money($voucher->min_order - $subtotal) . " more to use voucher {$code}.";

            return $best;
        }

        if ($off <= $amount) {
            $this->voucherNote = "Your {$offer->name} saves you more, so voucher {$code} was not used.";

            return $best;
        }

        return ['discount' => $off, 'discount_label' => "Voucher {$code}", 'voucher_id' => $voucher->id];
    }

    private function shippingFor(int $subtotal): int
    {
        $freeOver = $this->store->freeShippingOver * 100;

        return $freeOver > 0 && $subtotal >= $freeOver ? 0 : $this->store->shippingFee * 100;
    }

    /**
     * @return array{0: int, 1: string, 2: string} product ID, colour and size
     */
    private function parts(int|string $key): array
    {
        [$productId, $colour, $size] = explode(self::KEY_SEPARATOR, (string) $key) + [1 => '', 2 => ''];

        return [(int) $productId, $colour, $size];
    }

    /** A line's name for messages, such as Silk Set (Wine, M). */
    public function label(Product $product, string $colour, string $size): string
    {
        $options = implode(', ', array_filter([$colour, $size], static fn (string $option): bool => $option !== ''));

        return $options === '' ? $product->name : "{$product->name} ({$options})";
    }

    /**
     * @param array<int|string, int> $items
     */
    private function save(array $items): void
    {
        $this->session->set(self::SESSION_KEY, array_filter($items, static fn (int $quantity): bool => $quantity > 0));
    }
}

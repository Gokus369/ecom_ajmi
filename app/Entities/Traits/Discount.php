<?php

namespace App\Entities\Traits;

use App\Entities\Offer;

/** What an offer and a voucher share: a percent or amount off, above a minimum. */
trait Discount
{
    /** What comes off a subtotal in paise. Nothing below the minimum, and never more than the goods. */
    public function amountOff(int $subtotal): int
    {
        if ($subtotal < $this->min_order) {
            return 0;
        }

        $amount = $this->discount_type === Offer::PERCENT
            ? intdiv($subtotal * $this->discount_value, 100)
            : $this->discount_value;

        return min($amount, $subtotal);
    }

    /** Read as $thing->discount_text, such as 20% off or ₹500 off. */
    public function getDiscountText(): string
    {
        return ($this->discount_type === Offer::PERCENT ? "{$this->discount_value}%" : money($this->discount_value)) . ' off';
    }

    /** The value as the admin form shows it, a whole percent or rupees. */
    public function discountInput(): string
    {
        return $this->discount_type === Offer::PERCENT ? (string) $this->discount_value : money_input($this->discount_value);
    }
}

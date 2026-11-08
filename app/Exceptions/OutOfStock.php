<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Someone else bought the last of a product between cart and checkout.
 */
class OutOfStock extends RuntimeException
{
    public function __construct(public readonly string $productName)
    {
        parent::__construct("Not enough stock for {$productName}.");
    }
}

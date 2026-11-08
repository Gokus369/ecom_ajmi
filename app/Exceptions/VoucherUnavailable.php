<?php

namespace App\Exceptions;

use RuntimeException;

/** The voucher's last use went to someone else while this order was being placed. */
class VoucherUnavailable extends RuntimeException
{
}

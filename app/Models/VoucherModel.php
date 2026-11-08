<?php

namespace App\Models;

use App\Entities\Voucher;
use CodeIgniter\Model;

class VoucherModel extends Model
{
    /** No 0/O or 1/I/L, so a code survives being read out over the phone. */
    private const CODE_ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
    private const CODE_LENGTH   = 8;

    protected $table         = 'vouchers';
    protected $primaryKey    = 'id';
    protected $returnType    = Voucher::class;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'code', 'batch', 'name', 'discount_type', 'discount_value', 'min_order',
        'expires_on', 'max_uses', 'used_count', 'is_active',
    ];

    public function findUsable(string $code): ?Voucher
    {
        $voucher = $this->where('code', strtoupper(trim($code)))->first();

        return $voucher?->isUsable(store_today()) ? $voucher : null;
    }

    /**
     * Takes a use only if one is left, as one atomic statement, so two shoppers
     * can never both spend the last one.
     */
    public function redeem(int $id): bool
    {
        $this->db->query(
            'UPDATE ' . $this->db->prefixTable($this->table) . ' SET used_count = used_count + 1 WHERE id = ? AND is_active = 1 AND used_count < max_uses',
            [$id],
        );

        return $this->db->affectedRows() === 1;
    }

    public function release(int $id): void
    {
        $this->db->query(
            'UPDATE ' . $this->db->prefixTable($this->table) . ' SET used_count = used_count - 1 WHERE id = ? AND used_count > 0',
            [$id],
        );
    }

    /**
     * Makes a batch of unique codes that share their details.
     *
     * @param array<string, mixed> $details
     *
     * @return string the batch, to find them again
     */
    public function generate(array $details, string $prefix, int $quantity): string
    {
        $batch = bin2hex(random_bytes(8));
        $codes = [];

        while (count($codes) < $quantity) {
            $code = "{$prefix}-" . $this->randomPart();

            if (! isset($codes[$code]) && $this->where('code', $code)->countAllResults() === 0) {
                $codes[$code] = true;
            }
        }

        $this->insertBatch(array_map(
            static fn (string $code): array => [...$details, 'code' => $code, 'batch' => $batch],
            array_keys($codes),
        ));

        return $batch;
    }

    private function randomPart(): string
    {
        $last = strlen(self::CODE_ALPHABET) - 1;
        $part = '';

        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $part .= self::CODE_ALPHABET[random_int(0, $last)];
        }

        return $part;
    }
}

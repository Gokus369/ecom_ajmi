<?php

namespace App\Controllers\Admin\Traits;

use App\Entities\Offer;

/** The discount fields shared by the offer and voucher forms. */
trait ReadsDiscount
{
    /**
     * @return array<string, mixed>
     */
    private function discountRules(): array
    {
        $money   = 'regex_match[/^\d{1,7}(\.\d{1,2})?$/]';
        $percent = $this->request->getPost('discount_type') === Offer::PERCENT;

        return [
            'discount_type' => [
                'rules'  => 'required|in_list[percent,amount]',
                'errors' => ['required' => 'Please choose a discount type.', 'in_list' => 'Please choose a discount type.'],
            ],
            'discount_value' => [
                'rules'  => $percent ? ['required', 'is_natural_no_zero', 'less_than_equal_to[90]'] : ['required', $money, 'greater_than[0]'],
                'errors' => [
                    'required'           => 'Please enter the discount.',
                    'is_natural_no_zero' => 'Enter a whole percent, from 1 to 90.',
                    'less_than_equal_to' => 'A percent discount can be up to 90.',
                    'regex_match'        => 'Enter the amount as a number, like 500 or 499.50.',
                    'greater_than'       => 'Enter an amount above 0.',
                ],
            ],
            'min_order' => [
                'rules'  => ['permit_empty', $money],
                'errors' => ['regex_match' => 'Enter the minimum order as a number, like 2000.'],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $data validated form data
     *
     * @return array{discount_type: string, discount_value: int, min_order: int}
     */
    private function discountFields(array $data): array
    {
        return [
            'discount_type'  => $data['discount_type'],
            'discount_value' => $data['discount_type'] === Offer::PERCENT ? (int) $data['discount_value'] : to_minor($data['discount_value']),
            'min_order'      => to_minor((string) (($data['min_order'] ?? '') ?: '0')),
        ];
    }
}

<?php

namespace App\Controllers\Admin;

use App\Controllers\Admin\Traits\ReadsDiscount;
use App\Controllers\BaseController;
use App\Entities\Voucher;
use App\Models\VoucherModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Store;

class Vouchers extends BaseController
{
    use ReadsDiscount;

    private const PER_PAGE = 50;

    public function index(): string
    {
        $vouchers = $this->listed();

        return view('admin/vouchers/index', [
            'title'    => 'Vouchers',
            'batch'    => $this->batch(),
            'vouchers' => $vouchers->orderBy('id', 'DESC')->paginate(self::PER_PAGE),
            'pager'    => $vouchers->pager,
            'today'    => store_today(),
        ]);
    }

    public function generate(): RedirectResponse
    {
        $back = redirect()->route('admin.vouchers');

        if (! $this->validateData($this->request->getPost(), $this->rules())) {
            return $back->withInput()->with('errors', $this->validator->getErrors());
        }

        $data    = $this->validator->getValidated();
        $expires = ($data['expires_on'] ?? '') ?: null;

        if ($expires !== null && $expires < store_today()) {
            return $back->withInput()->with('errors', ['expires_on' => 'Please choose a date that has not passed.']);
        }

        $prefix   = strtoupper(($data['prefix'] ?? '') ?: config(Store::class)->shortName);
        $quantity = (int) $data['quantity'];

        $batch = model(VoucherModel::class)->generate([
            ...$this->discountFields($data),
            'name'       => trim($data['name']),
            'expires_on' => $expires,
            'max_uses'   => (int) $data['max_uses'],
            'is_active'  => 1,
        ], $prefix, $quantity);

        return redirect()->to(url_to('admin.vouchers') . '?batch=' . $batch)->with('message', "{$quantity} new vouchers are ready.");
    }

    public function toggle(int $id): RedirectResponse
    {
        $voucher = model(VoucherModel::class)->find($id);

        if ($voucher === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        model(VoucherModel::class)->update($id, ['is_active' => $voucher->is_active ? 0 : 1]);

        return redirect()->back()->with('message', "{$voucher->code} is now " . ($voucher->is_active ? 'off.' : 'on.'));
    }

    public function export(): ResponseInterface
    {
        $file = fopen('php://temp', 'r+b');

        fputcsv($file, ['Code', 'Name', 'Discount', 'Minimum order', 'Expires', 'Uses per code', 'Used'], escape: '');

        foreach ($this->listed()->orderBy('id', 'ASC')->findAll() as $voucher) {
            fputcsv($file, array_map($this->safeCell(...), [
                $voucher->code,
                $voucher->name,
                $voucher->discount_text,
                money($voucher->min_order),
                $voucher->expires_on ?? 'No expiry',
                (string) $voucher->max_uses,
                (string) $voucher->used_count,
            ]), escape: '');
        }

        rewind($file);
        $csv = stream_get_contents($file);
        fclose($file);

        // The mark at the start tells Excel the rupee sign is UTF-8.
        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="vouchers.csv"')
            ->setBody("\xEF\xBB\xBF" . $csv);
    }

    /** Spreadsheets run a cell that starts like a formula, so those are made plain text. */
    private function safeCell(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'{$value}" : $value;
    }

    private function listed(): VoucherModel
    {
        $vouchers = model(VoucherModel::class);

        if ($this->batch() !== null) {
            $vouchers->where('batch', $this->batch());
        }

        return $vouchers;
    }

    private function batch(): ?string
    {
        $batch = $this->request->getGet('batch');

        return is_string($batch) && $batch !== '' ? $batch : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            ...$this->discountRules(),
            'name' => [
                'rules'  => 'required|max_length[100]',
                'errors' => ['required' => 'Please name this batch.', 'max_length' => 'Names can be up to 100 characters.'],
            ],
            'prefix' => [
                'rules'  => ['permit_empty', 'regex_match[/^[A-Za-z0-9]{1,10}$/]'],
                'errors' => ['regex_match' => 'Use up to 10 letters and numbers, with no spaces.'],
            ],
            'quantity' => [
                'rules'  => 'required|is_natural_no_zero|less_than_equal_to[500]',
                'errors' => [
                    'required'           => 'Please say how many to make.',
                    'is_natural_no_zero' => 'Enter a whole number, from 1 to 500.',
                    'less_than_equal_to' => 'You can make up to 500 at a time.',
                ],
            ],
            'max_uses' => [
                'rules'  => 'required|is_natural_no_zero|less_than_equal_to[100000]',
                'errors' => [
                    'required'           => 'Please say how many times each code works.',
                    'is_natural_no_zero' => 'Enter a whole number, 1 or more.',
                    'less_than_equal_to' => 'That is too many uses.',
                ],
            ],
            'expires_on' => [
                'rules'  => 'permit_empty|valid_date[Y-m-d]',
                'errors' => ['valid_date' => 'Please choose a date.'],
            ],
        ];
    }
}

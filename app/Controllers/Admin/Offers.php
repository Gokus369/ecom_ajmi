<?php

namespace App\Controllers\Admin;

use App\Controllers\Admin\Traits\ReadsDiscount;
use App\Controllers\BaseController;
use App\Entities\Offer;
use App\Models\OfferModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

class Offers extends BaseController
{
    use ReadsDiscount;

    private const PER_PAGE = 25;

    public function index(): string
    {
        $offers = model(OfferModel::class);

        return view('admin/offers/index', [
            'title'  => 'Festival offers',
            'offers' => $offers->orderBy('ends_on', 'DESC')->orderBy('id', 'DESC')->paginate(self::PER_PAGE),
            'pager'  => $offers->pager,
            'today'  => store_today(),
        ]);
    }

    public function new(): string
    {
        return view('admin/offers/form', [
            'title' => 'New offer',
            'offer' => new Offer(['discount_type' => Offer::PERCENT, 'discount_value' => 0, 'min_order' => 0, 'is_active' => true]),
        ]);
    }

    public function create(): RedirectResponse
    {
        return $this->save();
    }

    public function edit(int $id): string
    {
        $offer = $this->findOrFail($id);

        return view('admin/offers/form', ['title' => "Edit {$offer->name}", 'offer' => $offer]);
    }

    public function update(int $id): RedirectResponse
    {
        return $this->save($this->findOrFail($id));
    }

    public function delete(int $id): RedirectResponse
    {
        $offer = $this->findOrFail($id);

        model(OfferModel::class)->delete($id);

        return redirect()->route('admin.offers')->with('message', "{$offer->name} deleted.");
    }

    private function save(?Offer $existing = null): RedirectResponse
    {
        $back = $existing
            ? redirect()->route('admin.offers.edit', [$existing->id])
            : redirect()->route('admin.offers.new');

        if (! $this->validateData($this->request->getPost(), $this->rules())) {
            return $back->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = $this->validator->getValidated();

        if ($data['ends_on'] < $data['starts_on']) {
            return $back->withInput()->with('errors', ['ends_on' => 'The last day cannot be before the first day.']);
        }

        $fields = [
            ...$this->discountFields($data),
            'name'      => trim($data['name']),
            'starts_on' => $data['starts_on'],
            'ends_on'   => $data['ends_on'],
            'is_active' => empty($data['is_active']) ? 0 : 1,
        ];

        $offers = model(OfferModel::class);

        if ($existing === null) {
            $offers->insert($fields);
        } else {
            $offers->update($existing->id, $fields);
        }

        return redirect()->route('admin.offers')->with('message', "{$fields['name']} saved.");
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
                'errors' => ['required' => 'Please give the offer a name.', 'max_length' => 'Offer names can be up to 100 characters.'],
            ],
            'starts_on' => [
                'rules'  => 'required|valid_date[Y-m-d]',
                'errors' => ['required' => 'Please choose the first day.', 'valid_date' => 'Please choose the first day.'],
            ],
            'ends_on' => [
                'rules'  => 'required|valid_date[Y-m-d]',
                'errors' => ['required' => 'Please choose the last day.', 'valid_date' => 'Please choose the last day.'],
            ],
            'is_active' => 'permit_empty|in_list[1]',
        ];
    }

    private function findOrFail(int $id): Offer
    {
        $offer = model(OfferModel::class)->find($id);

        if ($offer === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $offer;
    }
}

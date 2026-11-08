<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Entities\Enquiry;
use App\Models\EnquiryModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

class Enquiries extends BaseController
{
    private const PER_PAGE = 25;

    public function index(): string
    {
        $enquiries = model(EnquiryModel::class);
        $counts    = $enquiries->countsByStatus();
        $status    = $this->request->getGet('status');
        $status    = is_string($status) && isset(Enquiry::STATUS_LABELS[$status]) ? $status : null;

        if ($status !== null) {
            $enquiries->where('status', $status);
        }

        return view('admin/enquiries/index', [
            'title'     => 'Enquiries',
            'status'    => $status,
            'counts'    => $counts,
            'enquiries' => $enquiries->newest()->paginate(self::PER_PAGE),
            'pager'     => $enquiries->pager,
        ]);
    }

    public function show(int $id): string
    {
        $enquiry = $this->findOrFail($id);

        return view('admin/enquiries/show', [
            'title'   => "Enquiry from {$enquiry->customer_name}",
            'enquiry' => $enquiry,
        ]);
    }

    /** Records what the enquiry sold for and where the customer came from. */
    public function sale(int $id): RedirectResponse
    {
        $this->findOrFail($id);

        $sources = implode(',', array_keys(Enquiry::SOURCE_LABELS));
        $rules   = [
            'order_value' => ['rules' => ['permit_empty', 'regex_match[/^\d{1,7}(\.\d{1,2})?$/]'], 'errors' => ['regex_match' => 'Enter the amount as a number, like 25000.']],
            'source'      => ['rules' => "required|in_list[{$sources}]", 'errors' => ['required' => 'Please choose where they came from.', 'in_list' => 'Please choose where they came from.']],
        ];

        if (! $this->validateData($this->request->getPost(), $rules)) {
            return redirect()->route('admin.enquiries.show', [$id])->withInput()->with('errors', $this->validator->getErrors());
        }

        $data  = $this->validator->getValidated();
        $value = ($data['order_value'] ?? '') === '' ? null : to_minor($data['order_value']);

        model(EnquiryModel::class)->update($id, ['order_value' => $value, 'source' => $data['source']]);

        return redirect()->route('admin.enquiries.show', [$id])->with('message', 'Saved.');
    }

    public function updateStatus(int $id): RedirectResponse
    {
        $enquiry = $this->findOrFail($id);
        $status  = (string) $this->request->getPost('status');

        if (! $enquiry->canMoveTo($status)) {
            return redirect()->route('admin.enquiries.show', [$id])
                ->with('error', 'That enquiry has already moved on. Please check its status and try again.');
        }

        model(EnquiryModel::class)->update($id, ['status' => $status]);

        return redirect()->route('admin.enquiries.show', [$id])
            ->with('message', 'This enquiry is now ' . strtolower(Enquiry::STATUS_LABELS[$status]) . '.');
    }

    private function findOrFail(int $id): Enquiry
    {
        $enquiry = model(EnquiryModel::class)->find($id);

        if ($enquiry === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $enquiry;
    }
}

<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Entities\Enquiry;
use App\Libraries\Analytics as Report;
use App\Models\EnquiryModel;
use App\Models\PostStatModel;
use CodeIgniter\HTTP\RedirectResponse;

class Analytics extends BaseController
{
    public function index(): string
    {
        return view('admin/analytics/index', [
            'title'  => 'Analytics',
            'report' => (new Report())->report(store_today()),
            'today'  => store_today(),
        ]);
    }

    /** For enquiries that arrive by DM, WhatsApp or in person, which the site never sees. */
    public function logEnquiry(): RedirectResponse
    {
        $types   = implode(',', array_keys(Enquiry::TYPE_LABELS));
        $sources = implode(',', array_keys(Enquiry::SOURCE_LABELS));
        $rules   = [
            'customer_name' => ['rules' => 'required|max_length[100]', 'errors' => ['required' => 'Please enter a name or Instagram handle.']],
            'phone'         => ['rules' => ['permit_empty', 'regex_match[/^[0-9+()\s-]{7,20}$/]'], 'errors' => ['regex_match' => 'Please enter a valid phone number.']],
            'source'        => ['rules' => "required|in_list[{$sources}]", 'errors' => ['required' => 'Please choose where it came from.', 'in_list' => 'Please choose where it came from.']],
            'type'          => ['rules' => "required|in_list[{$types}]", 'errors' => ['required' => 'Please choose a type.', 'in_list' => 'Please choose a type.']],
            'order_value'   => ['rules' => ['permit_empty', 'regex_match[/^\d{1,7}(\.\d{1,2})?$/]'], 'errors' => ['regex_match' => 'Enter the amount as a number, like 25000.']],
        ];

        if (! $this->validateData($this->request->getPost(), $rules)) {
            return redirect()->route('admin.analytics')->withInput()->with('errors', $this->validator->getErrors());
        }

        $data  = $this->validator->getValidated();
        $value = ($data['order_value'] ?? '') === '' ? null : to_minor($data['order_value']);

        model(EnquiryModel::class)->insert([
            'type'          => $data['type'],
            'status'        => $value === null ? Enquiry::PENDING : Enquiry::CLOSED,
            'customer_name' => trim($data['customer_name']),
            'phone'         => trim((string) ($data['phone'] ?? '')),
            'source'        => $data['source'],
            'order_value'   => $value,
        ]);

        return redirect()->route('admin.analytics')->with('message', 'Enquiry logged.');
    }

    public function logPost(): RedirectResponse
    {
        $rules = [
            'title'     => ['rules' => 'required|max_length[140]', 'errors' => ['required' => 'Please say which post this is.']],
            'posted_on' => ['rules' => 'required|valid_date[Y-m-d]', 'errors' => ['required' => 'Please choose the date it was posted.', 'valid_date' => 'Please choose the date it was posted.']],
            'saves'     => ['rules' => 'required|is_natural', 'errors' => ['required' => 'Enter the saves, or 0.', 'is_natural' => 'Enter a whole number.']],
            'shares'    => ['rules' => 'required|is_natural', 'errors' => ['required' => 'Enter the shares, or 0.', 'is_natural' => 'Enter a whole number.']],
        ];

        if (! $this->validateData($this->request->getPost(), $rules)) {
            return redirect()->route('admin.analytics')->withInput()->with('errors', $this->validator->getErrors());
        }

        model(PostStatModel::class)->insert($this->validator->getValidated());

        return redirect()->route('admin.analytics')->with('message', 'Post logged.');
    }

    public function deletePost(int $id): RedirectResponse
    {
        model(PostStatModel::class)->delete($id);

        return redirect()->route('admin.analytics')->with('message', 'Post removed.');
    }
}

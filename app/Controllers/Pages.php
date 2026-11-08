<?php

namespace App\Controllers;

use App\Models\EnquiryModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\I18n\Time;
use Config\Store;

class Pages extends BaseController
{
    private const ENQUIRY_FIELDS = ['customer_name', 'phone', 'email', 'event_date', 'details'];

    public function bespoke(): string
    {
        return view('pages/bespoke', [
            'title'           => 'Bespoke and bridal',
            'metaDescription' => 'Custom styling and bridal consultations with fashion designer Ajmi Anzari, from Kerala.',
            'today'           => $this->today(),
        ]);
    }

    public function enquire(string $type): RedirectResponse
    {
        $input = [];

        foreach (self::ENQUIRY_FIELDS as $field) {
            $value         = $this->request->getPost("{$type}_{$field}");
            $input[$field] = is_string($value) ? trim($value) : $value;
        }

        if (! $this->validateData($input, 'enquiry')) {
            return $this->refuse($type, $this->validator->getErrors());
        }

        $details = $this->validator->getValidated();
        $date    = ($details['event_date'] ?? '') ?: null;

        if ($date !== null && $date < $this->today()) {
            return $this->refuse($type, ['event_date' => 'Please choose a date that has not passed.']);
        }

        model(EnquiryModel::class)->insert([
            'type'          => $type,
            'customer_name' => $details['customer_name'],
            'phone'         => $details['phone'],
            'email'         => $details['email'],
            'event_date'    => $date,
            'details'       => ($details['details'] ?? '') ?: null,
        ]);

        return redirect()->route('bespoke')->with('message', 'Thank you. We have your booking and will contact you soon.');
    }

    /**
     * Sends the customer back to the form they used. Errors come in by field, and
     * the page names its fields by form.
     *
     * @param array<string, string> $errors
     */
    private function refuse(string $type, array $errors): RedirectResponse
    {
        $named = [];

        foreach ($errors as $field => $message) {
            $named["{$type}_{$field}"] = $message;
        }

        return redirect()->route('bespoke')->withInput()->with('errors', $named);
    }

    /** Dates are entered in the store's timezone, so today is too. */
    private function today(): string
    {
        return Time::now(config(Store::class)->timezone)->toDateString();
    }
}

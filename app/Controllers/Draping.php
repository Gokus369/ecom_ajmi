<?php

namespace App\Controllers;

use App\Models\BookingModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\I18n\Time;
use Config\Store;

class Draping extends BaseController
{
    public function index(): string
    {
        return view('pages/draping', [
            'title'           => 'Saree draping',
            'metaDescription' => 'Book saree draping for your wedding with ' . config(Store::class)->name . ', from Kochi, Kerala.',
            'today'           => $this->today(),
        ]);
    }

    public function book(): RedirectResponse
    {
        $input = array_map(static fn ($value) => is_string($value) ? trim($value) : $value, $this->request->getPost());

        if (! $this->validateData($input, 'booking')) {
            return redirect()->route('draping')->withInput()->with('errors', $this->validator->getErrors());
        }

        $booking = $this->validator->getValidated();

        if ($booking['wedding_date'] < $this->today()) {
            return redirect()->route('draping')->withInput()
                ->with('errors', ['wedding_date' => 'Please choose a date that has not passed.']);
        }

        $booking['ready_by'] = ($booking['ready_by'] ?? '') ?: null;
        $booking['notes']    = ($booking['notes'] ?? '') ?: null;

        $id    = model(BookingModel::class)->insert($booking);
        $saved = model(BookingModel::class)->find($id);
        $store = config(Store::class);

        return redirect()->route('draping')->with('booked', [
            'date'     => $saved->date_label,
            'whatsapp' => whatsapp_url("Hi {$store->shortName}, I've requested saree draping for {$saved->date_label} at {$saved->location}, for {$saved->people}."),
        ]);
    }

    /** Dates are entered in the store's timezone, so today is too. */
    private function today(): string
    {
        return Time::now(config(Store::class)->timezone)->toDateString();
    }
}

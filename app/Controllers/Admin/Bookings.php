<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Entities\Booking;
use App\Models\BookingModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

class Bookings extends BaseController
{
    private const PER_PAGE = 25;

    public function index(): string
    {
        $bookings = model(BookingModel::class);
        $counts   = $bookings->countsByStatus();
        $status   = $this->request->getGet('status');
        $status   = is_string($status) && isset(Booking::STATUS_LABELS[$status]) ? $status : null;

        if ($status !== null) {
            $bookings->where('status', $status);
        }

        return view('admin/bookings/index', [
            'title'    => 'Bookings',
            'status'   => $status,
            'counts'   => $counts,
            'bookings' => $bookings->soonestFirst()->paginate(self::PER_PAGE),
            'pager'    => $bookings->pager,
        ]);
    }

    public function show(int $id): string
    {
        $booking = $this->findOrFail($id);

        return view('admin/bookings/show', [
            'title'   => "Booking for {$booking->customer_name}",
            'booking' => $booking,
        ]);
    }

    public function updateStatus(int $id): RedirectResponse
    {
        $booking = $this->findOrFail($id);
        $status  = (string) $this->request->getPost('status');

        if (! $booking->canMoveTo($status)) {
            return redirect()->route('admin.bookings.show', [$id])
                ->with('error', 'That booking has already moved on. Please check its status and try again.');
        }

        model(BookingModel::class)->update($id, ['status' => $status]);

        return redirect()->route('admin.bookings.show', [$id])
            ->with('message', 'This booking is now ' . strtolower(Booking::STATUS_LABELS[$status]) . '.');
    }

    private function findOrFail(int $id): Booking
    {
        $booking = model(BookingModel::class)->find($id);

        if ($booking === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $booking;
    }
}

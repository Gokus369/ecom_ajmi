<?php

namespace App\Libraries;

use App\Entities\Enquiry;
use App\Entities\Order;
use App\Models\EnquiryModel;
use App\Models\OrderModel;
use App\Models\PostStatModel;
use App\Models\ProductModel;
use Config\Store;
use DateTimeImmutable;
use DateTimeZone;

/**
 * The two-week business review. Figures come only from what the site holds, and
 * a figure with nothing behind it is null rather than a misleading zero.
 *
 * A customer is matched across orders and enquiries by phone number.
 */
class Analytics
{
    private const PERIOD_SECONDS = 14 * 86400;
    private const PERIODS        = 6;
    private const MAX_ACTIONS    = 3;

    /** Figures compared with the previous period. */
    private const COMPARED = ['enquiries_per_week', 'conversion', 'orders', 'sales', 'average_order', 'repeat_share'];

    /** @var list<array{key: string, at: int, amount: int, referred: bool}> every sale ever */
    private array $sales = [];

    /** @var list<array{key: string, at: int, sold: bool}> every enquiry ever */
    private array $enquiries = [];

    /**
     * @return array<string, mixed>
     */
    public function report(string $today): array
    {
        $this->load();

        $timezone = new DateTimeZone(config(Store::class)->timezone);
        $end      = (new DateTimeImmutable("{$today} 23:59:59", $timezone))->getTimestamp();
        $history  = [];

        for ($i = self::PERIODS - 1; $i >= 0; $i--) {
            $history[] = $this->period($end - $i * self::PERIOD_SECONDS, $timezone);
        }

        $current  = $history[self::PERIODS - 1];
        $previous = $history[self::PERIODS - 2];
        $changes  = [];

        foreach (self::COMPARED as $figure) {
            $changes[$figure] = $this->change($current[$figure], $previous[$figure]);
        }

        $posts = model(PostStatModel::class)->top($current['from'], $current['to']);

        return [
            'current'   => $current,
            'previous'  => $previous,
            'changes'   => $changes,
            'history'   => $history,
            'top_posts' => $posts,
            'actions'   => $this->actions($current, $changes, $posts),
        ];
    }

    private function load(): void
    {
        $this->sales = $this->enquiries = [];

        $orders = model(OrderModel::class)->asArray()->select('id, phone, total, heard_from, created_at')
            ->where('status !=', Order::CANCELLED)->findAll();

        foreach ($orders as $order) {
            $this->sales[] = [
                'key'      => $this->customerKey($order['phone'], "order-{$order['id']}"),
                'at'       => strtotime("{$order['created_at']} UTC"),
                'amount'   => (int) $order['total'],
                'referred' => $order['heard_from'] === Enquiry::REFERRAL,
            ];
        }

        foreach (model(EnquiryModel::class)->asArray()->findAll() as $enquiry) {
            $key = $this->customerKey($enquiry['phone'], "enquiry-{$enquiry['id']}");
            $at  = strtotime("{$enquiry['created_at']} UTC");

            $this->enquiries[] = ['key' => $key, 'at' => $at, 'sold' => $enquiry['order_value'] !== null];

            if ($enquiry['order_value'] !== null) {
                $this->sales[] = [
                    'key'      => $key,
                    'at'       => $at,
                    'amount'   => (int) $enquiry['order_value'],
                    'referred' => $enquiry['source'] === Enquiry::REFERRAL,
                ];
            }
        }
    }

    /** The last ten digits, so +91 98470 12345 and 9847012345 are one customer. */
    private function customerKey(string $phone, string $fallback): string
    {
        $digits = substr(preg_replace('/\D/', '', $phone), -10);

        return $digits === '' ? $fallback : $digits;
    }

    /**
     * @return array<string, mixed> the 14 days ending at a moment
     */
    private function period(int $end, DateTimeZone $timezone): array
    {
        $start     = $end - self::PERIOD_SECONDS;
        $within    = static fn (array $row): bool => $row['at'] > $start && $row['at'] <= $end;
        $enquiries = array_values(array_filter($this->enquiries, $within));
        $sales     = array_values(array_filter($this->sales, $within));
        $converted = count(array_filter($enquiries, $this->converted(...)));
        $total     = array_sum(array_column($sales, 'amount'));
        $customers = [];

        foreach ($sales as $sale) {
            $customers[$sale['key']] = ($customers[$sale['key']] ?? false) || $sale['referred'] || $this->isRepeat($sale['key'], $end);
        }

        return [
            'from'               => (new DateTimeImmutable("@{$start}"))->modify('+1 second')->setTimezone($timezone)->format('Y-m-d'),
            'to'                 => (new DateTimeImmutable("@{$end}"))->setTimezone($timezone)->format('Y-m-d'),
            'enquiries'          => count($enquiries),
            'enquiries_per_week' => count($enquiries) / 2.0,
            'conversion'         => $enquiries === [] ? null : round($converted / count($enquiries) * 100, 1),
            'orders'             => count($sales),
            'sales'              => $total,
            'average_order'      => $sales === [] ? null : (int) round($total / count($sales)),
            'customers'          => count($customers),
            'repeat_share'       => $customers === [] ? null : round(count(array_filter($customers)) / count($customers) * 100, 1),
        ];
    }

    /** Sold outright, or the same customer ordered on the site afterwards. */
    private function converted(array $enquiry): bool
    {
        if ($enquiry['sold']) {
            return true;
        }

        foreach ($this->sales as $sale) {
            if ($sale['key'] === $enquiry['key'] && $sale['at'] >= $enquiry['at']) {
                return true;
            }
        }

        return false;
    }

    private function isRepeat(string $key, int $end): bool
    {
        $bought = array_filter($this->sales, static fn (array $sale): bool => $sale['key'] === $key && $sale['at'] <= $end);

        return count($bought) > 1;
    }

    private function change(float|int|null $current, float|int|null $previous): ?float
    {
        if ($current === null || $previous === null || $previous == 0) {
            return null;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }

    /**
     * Up to three next steps, the most urgent first, from these figures. Each names
     * the step of the customer's path it helps, so the first is the one to fix.
     *
     * @param array<string, mixed>       $current
     * @param array<string, float|null>  $changes
     * @param list<\App\Entities\PostStat> $posts
     *
     * @return list<array{step: string, title: string, why: string}>
     */
    private function actions(array $current, array $changes, array $posts): array
    {
        $actions = [];
        $waiting = model(EnquiryModel::class)->where('status', Enquiry::PENDING)->countAllResults();
        $falling = static fn (string $figure): bool => ($changes[$figure] ?? 0) < 0;

        if ($waiting > 0) {
            $actions[] = [
                'step'  => 'Enquiry',
                'title' => $waiting === 1 ? 'Reply to the 1 enquiry waiting for an answer' : "Reply to the {$waiting} enquiries waiting for an answer",
                'why'   => 'An unanswered enquiry is the cheapest sale to lose. Mark each one contacted once you have replied.',
            ];
        }

        if ($current['enquiries'] === 0 && $current['orders'] === 0) {
            $actions[] = [
                'step'  => 'Tracking',
                'title' => 'Log every DM and WhatsApp enquiry here',
                'why'   => 'Nothing was recorded in these two weeks, so nothing can be measured yet.',
            ];
        }

        if ($falling('conversion')) {
            $actions[] = [
                'step'  => 'Order',
                'title' => 'Follow up every open enquiry with a clear next step',
                'why'   => "Fewer enquiries became orders than last time ({$changes['conversion']}%). Send a price range, a lead time and a deposit request in one reply.",
            ];
        }

        if ($falling('enquiries_per_week')) {
            $actions[] = [
                'step'  => 'Discovery',
                'title' => $posts === [] ? 'Post a Reel showing a piece being made' : "Boost \"{$posts[0]->title}\", your most saved and shared post",
                'why'   => "Enquiries fell {$changes['enquiries_per_week']}% against the last two weeks. End the post with one next step, such as DM to book a fitting.",
            ];
        }

        if ($falling('average_order')) {
            $actions[] = [
                'step'  => 'Order',
                'title' => 'Offer a matching piece or an occasion bundle with each order',
                'why'   => "Average order value moved {$changes['average_order']}%. Raising it costs less than finding a new customer.",
            ];
        }

        if ($current['customers'] > 0 && ($current['repeat_share'] === 0.0 || $falling('repeat_share'))) {
            $actions[] = [
                'step'  => 'Retention',
                'title' => 'Ask this fortnight\'s customers for a review and a referral',
                'why'   => "{$current['repeat_share']}% of customers were repeat or referred. Add a voucher under Vouchers to thank anyone who refers a friend.",
            ];
        }

        if ($posts === []) {
            $actions[] = [
                'step'  => 'Tracking',
                'title' => 'Log your posts\' saves and shares below',
                'why'   => 'They show which content to repeat or boost.',
            ];
        }

        $unpriced = model(ProductModel::class)->published()->where('is_enquiry_only', 1)->countAllResults();

        if ($unpriced > 0) {
            $actions[] = [
                'step'  => 'Interest',
                'title' => 'Test a starting price on your enquiry-only pieces',
                'why'   => "{$unpriced} published pieces show no price. A range, such as from a price you choose, can save time on enquiries that were never going to buy.",
            ];
        }

        $actions[] = [
            'step'  => 'Retention',
            'title' => 'Test one festival offer or limited drop',
            'why'   => 'Compare the two weeks it runs with the two before, then keep it only if sales rose by more than the discount cost.',
        ];

        return array_slice($actions, 0, self::MAX_ACTIONS);
    }
}

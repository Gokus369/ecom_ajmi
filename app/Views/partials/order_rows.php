<?php /** The admin order table. Needs $orders. Call through partial(). */ ?>
<?php // A phone has no room for five columns, so under sm each order becomes a small card. ?>
<div class="table-wrap sm:max-h-[75vh]">
  <table class="table border-separate border-spacing-0 max-sm:block">
    <thead class="sticky top-0 z-1 max-sm:hidden [&_th]:shadow-[0_1px_0_var(--color-stone-200)]">
      <tr>
        <th scope="col">Order</th>
        <th scope="col">Customer</th>
        <th scope="col">Placed</th>
        <th scope="col">Status</th>
        <th scope="col" class="num">Total</th>
      </tr>
    </thead>
    <tbody class="max-sm:block max-sm:divide-y max-sm:divide-stone-100">
      <?php foreach ($orders as $order): ?>
        <tr class="relative transition-colors hover:bg-stone-50 max-sm:grid max-sm:grid-cols-[minmax(0,1fr)_auto] max-sm:gap-x-3 max-sm:gap-y-0.5 max-sm:px-4 max-sm:py-3 max-sm:[&>td]:border-0 max-sm:[&>td]:p-0 max-sm:[&>td]:whitespace-normal">
          <td class="max-sm:col-start-1 max-sm:row-start-1"><a class="font-medium text-brand-800 after:absolute after:inset-0" href="<?= esc(url_to('admin.orders.show', $order->id), 'attr') ?>"><?= esc($order->number) ?></a></td>
          <td class="max-sm:col-start-1 max-sm:row-start-2 max-sm:min-w-0"><?= esc($order->customer_name) ?><span class="block text-xs text-stone-500"><?= esc($order->phone) ?></span></td>
          <td class="max-sm:col-span-2 max-sm:row-start-3 max-sm:text-xs max-sm:text-stone-500"><?= esc(local_time($order->created_at)) ?></td>
          <td class="max-sm:col-start-2 max-sm:row-start-1 max-sm:justify-self-end"><span class="status status-<?= esc($order->status, 'attr') ?>"><?= esc($order->status_label) ?></span></td>
          <td class="num max-sm:col-start-2 max-sm:row-start-2 max-sm:font-medium"><?= esc(money($order->total)) ?></td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
</div>

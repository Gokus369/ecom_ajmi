<?php /** Needs $subtotal, $shipping and $total, in minor units. Optional: $discount and $discount_label. Call through partial(). */ ?>
<dl class="grid gap-2 text-sm">
  <div class="flex justify-between gap-4"><dt class="text-stone-600">Subtotal</dt><dd class="tabular-nums"><?= esc(money($subtotal)) ?></dd></div>
  <?php if (($discount ?? 0) > 0): ?>
    <div class="flex justify-between gap-4 text-emerald-700"><dt><?= esc($discount_label ?? 'Discount') ?></dt><dd class="tabular-nums">−<?= esc(money($discount)) ?></dd></div>
  <?php endif ?>
  <div class="flex justify-between gap-4"><dt class="text-stone-600">Delivery</dt><dd class="tabular-nums"><?= $shipping === 0 ? 'Free' : esc(money($shipping)) ?></dd></div>
  <div class="mt-1 flex justify-between gap-4 border-t border-stone-200 pt-3 text-base font-semibold"><dt>Total</dt><dd class="tabular-nums"><?= esc(money($total)) ?></dd></div>
</dl>

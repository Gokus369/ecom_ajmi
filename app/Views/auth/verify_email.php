<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
<?php $pending = auth('session')->getAuthenticator()->getPendingUser(); ?>
<p class="eyebrow">One last step</p>
<h1>Verify your email</h1>
<p class="mt-2 text-stone-600">Enter the 6-digit code sent to <?= esc($pending?->email ?? 'your email address') ?>.</p>

<?php if (session('error') !== null): ?>
  <p class="flash flash-danger mt-4" role="alert"><?= esc(session('error')) ?></p>
<?php endif ?>

<form class="mt-6 grid gap-4" action="<?= esc(url_to('auth-action-verify'), 'attr') ?>" method="post" novalidate data-submit-once>
  <?= csrf_field() ?>

  <?= partial('field', ['name' => 'token', 'label' => 'Verification code', 'required' => true, 'attributes' => 'class="text-center text-2xl! tracking-[0.3em]" inputmode="numeric" autocomplete="one-time-code" maxlength="6" autofocus']) ?>

  <button type="submit" class="btn btn-large btn-block">Verify and continue</button>
</form>

<p class="mt-6 text-center text-sm text-stone-600">Can't find it? Check your spam folder, or <a class="text-link" href="<?= esc(url_to('auth-action-show'), 'attr') ?>">send a new code</a>.</p>
<p class="mt-1 text-center text-sm"><a class="text-link" href="<?= esc(url_to('logout'), 'attr') ?>">Use a different email</a></p>
<?= $this->endSection() ?>

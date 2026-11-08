<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
<?php
$help = whatsapp_url('Hi ' . config(\Config\Store::class)->shortName . ", I can't sign in to my account.");
?>
<p class="eyebrow">Welcome back</p>
<h1>Sign in</h1>

<?php if (session('error') !== null): ?>
  <p class="flash flash-danger mt-4" role="alert"><?= esc(session('error')) ?></p>
<?php endif ?>

<?php if (session('message') !== null): ?>
  <p class="flash flash-ok mt-4" role="status"><?= esc(session('message')) ?></p>
<?php endif ?>

<?= partial('google_button') ?>

<form class="mt-6 grid gap-4" action="<?= esc(url_to('login'), 'attr') ?>" method="post" novalidate data-submit-once>
  <?= csrf_field() ?>

  <?= partial('field', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true, 'attributes' => 'autocomplete="email" inputmode="email"']) ?>
  <?= partial('field', ['name' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'attributes' => 'autocomplete="current-password"']) ?>

  <?php if (setting('Auth.sessionConfig')['allowRemembering']): ?>
    <label class="check">
      <input type="checkbox" name="remember" <?= old('remember') ? 'checked' : '' ?>>
      Keep me signed in
    </label>
  <?php endif ?>

  <button type="submit" class="btn btn-large btn-block">Sign in</button>
</form>

<p class="mt-6 text-center text-sm text-stone-600">New here? <a class="text-link" href="<?= esc(url_to('register'), 'attr') ?>">Create an account</a></p>
<?php if ($help): ?>
  <p class="mt-1 text-center text-sm text-stone-600">Forgot your password? <a class="text-link" href="<?= esc($help, 'attr') ?>" target="_blank" rel="noopener">Message us</a></p>
<?php endif ?>
<?= $this->endSection() ?>

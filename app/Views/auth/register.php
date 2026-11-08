<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
<p class="eyebrow">Join RUA</p>
<h1>Create your account</h1>
<p class="mt-2 text-stone-600">Keep your orders in one place and check out faster.</p>

<?php if (session('error') !== null): ?>
  <p class="flash flash-danger mt-4" role="alert"><?= esc(session('error')) ?></p>
<?php endif ?>

<?= partial('google_button') ?>

<form class="mt-6 grid gap-4" action="<?= esc(url_to('register'), 'attr') ?>" method="post" novalidate data-submit-once>
  <?= csrf_field() ?>

  <?= partial('field', ['name' => 'name', 'label' => 'Full name', 'required' => true, 'attributes' => 'autocomplete="name" maxlength="100"']) ?>
  <?= partial('field', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true, 'attributes' => 'autocomplete="email" inputmode="email" maxlength="254"']) ?>
  <?= partial('field', ['name' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'hint' => 'At least 8 characters.', 'attributes' => 'autocomplete="new-password"']) ?>
  <?= partial('field', ['name' => 'password_confirm', 'label' => 'Password again', 'type' => 'password', 'required' => true, 'attributes' => 'autocomplete="new-password"']) ?>

  <button type="submit" class="btn btn-large btn-block">Create account</button>
</form>

<p class="mt-6 text-center text-sm text-stone-600">Already have an account? <a class="text-link" href="<?= esc(url_to('login'), 'attr') ?>">Sign in</a></p>
<?= $this->endSection() ?>

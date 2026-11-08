<?php
$store    = config(\Config\Store::class);
$whatsapp = whatsapp_url("Hi {$store->shortName}, I'd like to ask about a bespoke piece.");
?>
<?php if ($whatsapp): ?>
  <button class="chat-launcher" type="button" aria-label="Chat with the <?= esc($store->shortName, 'attr') ?> assistant" aria-controls="chat" aria-expanded="false" hidden>
    <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 12a8 8 0 0 1-11.6 7.1L4 20l1-4.2A8 8 0 1 1 20 12Z"/></svg>
  </button>

  <section class="chat" id="chat" tabindex="-1" aria-label="<?= esc($store->shortName, 'attr') ?> assistant" data-whatsapp="<?= esc($whatsapp, 'attr') ?>" hidden>
    <header class="chat-head">
      <p><strong><?= esc($store->shortName) ?></strong><span>Automated replies</span></p>
      <button class="chat-close" type="button" aria-label="Close chat">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
      </button>
    </header>

    <div class="chat-log" role="log" aria-live="polite"></div>

    <?= form_open(url_to('chat'), ['class' => 'chat-form', 'autocomplete' => 'off']) ?>
      <label class="sr-only" for="chat-message">Your message</label>
      <input id="chat-message" type="text" name="message" maxlength="500" placeholder="Type a message" required>
      <button type="submit">Send</button>
    <?= form_close() ?>
  </section>

  <script src="<?= esc(asset_url('js/chat.js'), 'attr') ?>" defer></script>
<?php endif ?>

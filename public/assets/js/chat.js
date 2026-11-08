// The website assistant. Without this file its button stays hidden.
(() => {
  const chat = document.getElementById('chat');
  if (!chat) return;

  const log = chat.querySelector('.chat-log');
  const form = chat.querySelector('form');
  const input = form.elements.message;
  const launcher = document.querySelector('.chat-launcher');
  let started = false;
  let busy = false;

  const add = (element) => {
    log.append(element);
    log.scrollTop = log.scrollHeight;
    return element;
  };

  // Everything goes in as text, so a customer's message can never become markup.
  const bubble = (who, text) => {
    const element = document.createElement('p');
    element.className = `chat-bubble chat-${who}`;
    element.textContent = text;
    return add(element);
  };

  const showLinks = (links) => {
    links.forEach(({ label, url }) => {
      const link = document.createElement('a');
      link.className = 'chat-link';
      link.href = url;
      link.textContent = label;
      if (/^https?:/.test(url) && !url.startsWith(window.location.origin)) {
        link.target = '_blank';
        link.rel = 'noopener';
      }
      add(link);
    });
  };

  const showOptions = (options) => {
    const group = document.createElement('div');
    group.className = 'chat-options';
    options.forEach(({ id, title }) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.textContent = title;
      button.addEventListener('click', () => send(id, title));
      group.append(button);
    });
    add(group);
  };

  const send = async (message, shown) => {
    if (busy) return;
    busy = true;
    const options = log.querySelector('.chat-options');
    // Removing a focused button would drop keyboard users back to the top of the page.
    if (options?.contains(document.activeElement)) chat.focus();
    options?.remove();
    if (shown) bubble('customer', shown);

    const typing = bubble('assistant', '');
    typing.classList.add('chat-typing');
    typing.setAttribute('aria-label', 'The assistant is typing');

    const body = new FormData(form);
    body.set('message', message);

    try {
      const response = await fetch(form.action, {
        method: 'POST',
        body,
        headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
      });
      if (!response.ok) throw new Error(`Chat failed with ${response.status}`);
      const reply = await response.json();

      typing.remove();
      const answer = bubble('assistant', reply.text);
      showLinks(reply.links);
      showOptions(reply.options.length ? reply.options : [{ id: 'menu', title: 'Main menu' }]);
      // On a small screen the options push the answer off the top, so bring its first line back.
      answer.scrollIntoView({ block: 'nearest' });
    } catch {
      typing.remove();
      bubble('assistant', 'Sorry, the assistant could not answer just now. Please message us on WhatsApp.');
      showLinks([{ label: 'Open WhatsApp', url: chat.dataset.whatsapp }]);
    }
    busy = false;
  };

  const toggle = (open) => {
    chat.hidden = !open;
    launcher.hidden = open;
    launcher.setAttribute('aria-expanded', String(open));

    if (!open) {
      launcher.focus();
      return;
    }
    if (!started) {
      started = true;
      send('menu');
    }
    // On touch screens focusing the box would open the keyboard over the options.
    (window.matchMedia('(pointer: fine)').matches ? input : chat).focus();
  };

  launcher.addEventListener('click', () => toggle(true));
  chat.querySelector('.chat-close').addEventListener('click', () => toggle(false));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !chat.hidden) toggle(false);
  });

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    const message = input.value.trim();
    if (!message || busy) return;
    input.value = '';
    send(message, message);
  });

  launcher.hidden = false;
})();

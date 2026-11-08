# RUA by Ajmi Anzari

The online store for RUA, a boutique couture label from Kerala. Built on CodeIgniter 4: a storefront, a cart with guest checkout or customer accounts, cash on delivery, WhatsApp enquiries for bespoke and bridal pieces, and an admin for products, categories and orders.

Requires PHP 8.2+ with the `intl`, `mbstring` and `gd` extensions, plus MySQL or MariaDB.

## Run it locally

1. Start **MySQL** in the XAMPP Control Panel.
2. From this folder, start the site:

   ```
   php spark serve
   ```

3. Open <http://localhost:8080> for the store and <http://localhost:8080/login> for the admin.

## Set it up from scratch

```
copy env .env
php spark key:generate
```

In `.env`, set `CI_ENVIRONMENT = development`, `app.baseURL` and the `database.default.*` values, and create that database. Then:

```
php spark migrate --all
php spark db:seed RuaCatalogueSeeder    # the real pieces from Instagram
php spark db:seed AdminSeeder           # prints the admin password once
```

`admin.email` in `.env` sets the admin address. To change a password later:

```
php spark shield:user password -e admin@example.com
```

## Changing the design

The site is styled with [Tailwind CSS](https://tailwindcss.com/). Pages use Tailwind classes in their markup, and `resources/css/app.css` holds the colours, fonts and the few pieces repeated across pages, such as buttons and form fields.

`public/assets/css/app.css` is built from those, so never edit it by hand. After changing a view or the source file, rebuild it:

```
npm install        # once
npm run build      # or: npm run dev, which rebuilds as you save
```

Node is only needed to build. The live server just serves the built file, so upload it with the rest of `public/`.

## Settings

Set these in `.env`. The defaults are in `app/Config/Store.php`.

| Setting | Meaning |
|---|---|
| `store.name`, `store.shortName`, `store.byline` | The full name, and the two lines of the wordmark |
| `store.tagline`, `store.description` | The headline line, and the text shown to search engines |
| `store.whatsapp` | Business number with country code; empty hides every WhatsApp button |
| `store.instagram` | Handle without the @; empty hides every Instagram link |
| `store.phone` | Boutique number with country code; empty hides every Call button |
| `store.address` | Boutique address on one line; empty hides the Visit section and directions |
| `store.contactEmail` | Shown in the footer when set |
| `store.motion` | `respect` follows each visitor's reduce-motion setting; `always` animates for everyone |
| `store.currency` | Symbol shown before prices |
| `store.timezone` | Timezone for displayed dates; the database stores UTC |
| `store.shippingFee` | Flat delivery fee in whole units; `0` makes delivery free |
| `store.freeShippingOver` | Free delivery at or above this amount; `0` turns it off |
| `store.lowStockAt` | Stock level that appears as low on the dashboard |

Home page wording is in `app/Views/home/index.php`. Colours and fonts are the tokens at the top of `public/assets/css/app.css`.

## Adding pieces

- A piece can have up to 8 photos. Chosen photos show as previews before saving, each pick adds to the last, and the × on a preview drops it. On the edit page, pick the **Main photo**, which is the one shown on cards, in the cart and on orders, or tick **Remove** to delete a saved photo for good. The product page shows them all, and shoppers can swipe or use the thumbnails.
- Photos are cropped to a 4:5 portrait, so keep the piece centred in the frame.
- Tick **Enquiry only** for bridal and made-to-order pieces. The price and cart disappear, and the page shows "Price on request" and a WhatsApp button. Price and stock are ignored, so 0 is fine for both.
- Every other piece can be bought through the cart, and also has a WhatsApp button for questions or customising.
- Tick the piece's **Colours** and **Sizes**, and a grid asks for the stock of each combination. Shoppers must pick one of each, and a combination at 0 shows as sold out. Leave both unticked for a piece with no options, which uses the plain **Stock** box. The price is the same for every combination.

## Colours and sizes

The lists live in one place, under **Colours and sizes** in the admin, and products tick from them, so a name is never typed twice or spelt two ways.

- Each colour has a name and a swatch, and each size has a name. **Order** sets the order shoppers see, lower numbers first. Sizes follow it too, so S comes before M however they were added.
- Renaming an item changes it on every piece, and past orders keep the name they were placed with.
- An item in use cannot be deleted. Untick it on those pieces first.
- The starting lists are set in the migration `CreateProductVariants`. Change them in the admin.

Stock lives on `product_variants`, one row per combination. `products.stock` is the total of those rows, kept up to date automatically, so badges and the dashboard can read it directly. Low stock on the dashboard counts the whole piece, so a piece can show as well stocked while one combination is out.

## The scroll film

The home page opens with the current RUA brand film: wine and gold silk unfolds to reveal the bridal boutique. It plays forwards and backwards as you scroll, using `public/assets/js/scroll-film.js` and `app/Views/partials/scroll_film.php`. The branding is part of the frames, so no captions cover the imagery.

- `public/assets/rua-scroll-frames-4k-webp/webp/` holds 211 frames at 3840 x 2160, about 34 MB, for desktops and tablets.
- `public/assets/rua-scroll-frames-mobile/webp/` holds the same film at 1280 x 720, about 9 MB, for phones. They load every second frame plus the final frame, about 4 MB, because 4K would cost them 34 MB of data.
- Files are named `frame_0001.webp`, `frame_0002.webp` and so on, with no gaps. The site discovers the continuous sequence and uses its newest modification time for cache invalidation.
- The film's speed is the height of `.film-scroll` in `resources/css/app.css`: taller plays slower, but visitors scroll further to reach the store. Run `npm run build` after changing it. `GLIDE_MS` in `scroll-film.js` sets how gently it catches up with the scroll.
- Portrait screens crop the landscape film to fill the height, so it opens smaller to keep the RUA wordmark whole, over a blur of the first frame, then zooms to full screen as the wordmark leaves. If new frames move the wordmark, update the constants at the top of `scroll-film.js`.

The normal hero shows without JavaScript, with data saver on, if the film cannot load, and for visitors with reduce motion enabled unless `store.motion` is `always`.

## Scroll animations

Motion is switched on per element in the views, on the browsing pages only. Add `data-reveal` for a gentle rise, `data-reveal="unroll"` to open top to bottom like cloth, or `data-reveal="draw"` to run a line across. Elements already on screen when the page loads stay as they are, and the hero plays once on load. Cart, checkout and admin stay still on purpose.

Visitors with reduce motion or Windows "Animation effects" off see everything still, unless `store.motion` is `always`. Browsers without JavaScript show everything with no scroll animation.

## Collection covers

A panel on the home page uses `public/assets/collections/{category-slug}.webp`, 720×960, if that file exists, and otherwise the newest product photo in that collection. To set a cover, add its file. To go back to the product photo, delete it.

## Saree draping

Customers request saree draping for their wedding at `/saree-draping`, linked from the menu, the home page, the footer and the chat.

- The form asks for name, phone, wedding date, venue or place and number of people, with an optional ready-by time and notes.
- Requests appear in the admin under **Bookings**, soonest wedding first. Each moves from New to Confirmed to Completed, and can be cancelled before it is completed.
- A request is not a confirmed slot. The site does not check availability, and the customer is told you will contact them to confirm.
- There is no email or phone alert when a request arrives. New requests show as a count in the admin menu and on the dashboard, with the next weddings listed there, and customers are offered a button to also send their request on WhatsApp.
- Wording is in `app/Views/pages/draping.php`. Prices, durations and travel are left out until they are confirmed.

## Enquiries

The Custom styling and Bridal consultation cards on `/bespoke` each have a short booking form. It asks for a name, phone and email, with an optional date and details.

- Pressing **Book** saves the booking and thanks the customer on the same page. It does not involve WhatsApp.
- Bookings from these forms appear in the admin under **Enquiries**, newest first, and the dashboard shows how many are new. Each moves from New to Contacted to Closed. Closed is final.
- Nobody is alerted when one arrives, so check **Enquiries** regularly. Saree draping requests are separate, under **Bookings**.
- The chat and the **Enquire on WhatsApp** button on product pages still go straight to WhatsApp, so those conversations are not saved here.
- Wording is in `app/Views/pages/bespoke.php` and `app/Views/partials/enquiry_form.php`.

## Website chat

The round button on every store page opens an automated assistant. It works with no outside account, and it shows as "Automated replies" so customers know what they are talking to.

- It offers Custom piece, Bridal consultation, Shop the collection, Track my order, Visit the boutique and Talk to the team, and it also understands typed messages with those words in English. Anything else gets the menu.
- An order number returns its status and order page link, never the customer's details.
- Custom, bridal, product and "talk to the team" messages end with a **Continue on WhatsApp** button, which opens a real chat with a message ready to send.
- Nothing is stored. Each message is answered and forgotten, and a refresh clears the conversation.
- Each visitor can send 30 messages a minute, and an error offers the WhatsApp link instead.
- It needs `store.whatsapp`. If that is empty, the chat is hidden.
- Without JavaScript, the button stays hidden and the WhatsApp links elsewhere on the site still work.

Wording is in `app/Libraries/Assistant.php`. It uses only details from the settings, so add opening hours there once they are confirmed. The same answers power the WhatsApp assistant below.

## WhatsApp assistant

This is separate from the website chat: it answers messages sent to the business number inside WhatsApp. It is off until it is connected to Meta, and until then people who message the number get no automatic reply. The site's WhatsApp links need no changes, because their ready-made messages go to the matching answer.

- It gives the same answers as the website chat, with a tappable menu.
- Handed-over conversations go to a person, and the assistant stays quiet with that customer for 24 hours. Sending `menu` brings it back.

To connect it, the site must be live on a public `https://` address.

1. In [Meta for Developers](https://developers.facebook.com/), create a Business app and add the WhatsApp product.
2. Connect the business number. Decide how the team will reply first: see the note below.
3. Add these to the server's `.env`, using a long random value of your own for `verifyToken`:

   ```
   whatsapp.phoneNumberId = ''
   whatsapp.accessToken = ''
   whatsapp.appSecret = ''
   whatsapp.verifyToken = ''
   ```

   `accessToken` should be a permanent system user token. `appSecret` is under App settings > Basic. `whatsapp.graphVersion` sets the Graph API version.

4. In the app's WhatsApp configuration, set the callback URL to `https://your-domain/webhooks/whatsapp`, enter the same verify token, and subscribe to the `messages` field.
5. Add a payment method. Meta charges for messages sent beyond its free allowance.
6. Send the number "hi" from another phone, and check `writable/logs/` if no reply arrives.

**How the team replies:** the assistant passes conversations to "the team", but this site has no inbox. Keep the number in the WhatsApp Business app using Meta's coexistence setup, so the team sees every chat there. Check that it is available for the number before connecting. A number moved fully to the API can no longer be read in the app.

## How orders work

- Customers can check out as guests or with an account. Either way they receive a private order link, such as `/orders/72FMHC2ZBQ`.
- Customers find the orders placed while signed in under **My orders**. An order is linked to the account it was placed from, never to a matching address, because addresses are only verified once email codes are on.
- Customers can also use **Continue with Google**, once it is connected. See [Google sign-in](#google-sign-in).
- There is no self-service password reset, because that needs an email service. Reset one with `php spark shield:user password -e customer@example.com`.
- An order moves through New, Confirmed, Shipped and Delivered. It can be cancelled at any step before Delivered, which puts its items back into stock.
- Prices and names are copied onto the order, so later product edits do not change past orders.
- Deleting a product hides it but keeps the record for past orders.

## Tests

```
php vendor/bin/phpunit --no-coverage
```

The tests use an in-memory SQLite database and never touch your real data.

## Google sign-in

The **Continue with Google** button stays hidden until these are set.

1. In the [Google Cloud console](https://console.cloud.google.com/), create a project.
2. Under **Google Auth Platform**, set the app name and support email, then choose **External**.
3. Under **Clients**, create a **Web application** client, and add these as **Authorized redirect URIs**:
   - `http://localhost:8080/auth/google/callback` for this computer
   - `https://your-domain/auth/google/callback` for the live site
4. In `.env`, set `google.clientId` and `google.clientSecret`.
5. Before going live, publish the app in the console. Until then only the test users you list there can sign in.

Google only opens accounts it created. If a password account already uses the address, Google sign-in asks for the password instead, because addresses are not verified at sign up and joining them could hand an account to whoever registered the address first. That also means Google can never open an admin account.

## Email codes

New customers can confirm their address with a 6-digit code sent by email. This turns on by itself once `email.SMTPHost` is set. Until then, sign-up works without a code.

1. Get SMTP details from a mail provider. With Gmail, turn on 2-Step Verification, then create an [app password](https://myaccount.google.com/apppasswords).
2. Add these to `.env`, using your own address and password:

   ```
   email.protocol = smtp
   email.SMTPHost = smtp.gmail.com
   email.SMTPPort = 587
   email.SMTPCrypto = tls
   email.SMTPUser = 'you@gmail.com'
   email.SMTPPass = 'your app password'
   email.fromEmail = 'you@gmail.com'
   ```

3. Restart the site, then create a test account to check that the code arrives.

- The code is asked for once, at sign-up. An unconfirmed account cannot open **My orders**, and signing in again sends a new code.
- Google accounts skip this, because Google has already verified the address. Accounts created earlier stay active.
- Each visitor gets 10 sign-in, sign-up and code requests a minute, which also limits guessing and repeat emails.
- If the email cannot be sent, the customer sees a message and `writable/logs/` records it.
- A customer who mistypes their address can choose **Use a different email**. The unconfirmed account stays, so that address cannot sign up until the account is removed.

## Offers and vouchers

- **Festival offers** take a percent or amount off every order between two dates, and show a banner across the store while they run. They start and stop by themselves.
- **Vouchers** are unique codes made in batches under Admin, Vouchers. Customers enter one in the cart. Download a batch as a CSV to send out.
- An offer and a voucher never add together. The customer gets whichever saves more, and a voucher that does not win stays unspent.
- A voucher's use is taken when the order is placed, and given back if the order is cancelled.
- Free delivery is judged on the order after its discount.

## Two-week review

Admin, Analytics compares the last two weeks with the two before, and shows the last 12 weeks.

- Website orders and enquiries are counted by themselves. Log DM, WhatsApp and walk-in enquiries on that page, and fill in **Sold for** once one becomes an order.
- Set **Came from** on an order or enquiry to count referrals. Customers are not asked.
- Saves and shares come from Instagram's insights, so enter them by hand.
- Customers are matched by phone number, so give one wherever you can.
- It shows sales, not profit, because costs are not recorded.

## Going live

1. Point the domain's document root at the **`public/`** folder. Nothing else may be reachable by URL. If `/.env` opens in a browser, stop and fix this first.
2. In the server's `.env`, set `CI_ENVIRONMENT = production`, the real `https://` address in `app.baseURL`, `app.forceGlobalSecureRequests = true`, `cookie.secure = true`, and the database details.
3. Generate a new key with `php spark key:generate`. Keep it safe, and never reuse the development key.
4. Install and prepare:

   ```
   composer install --no-dev --optimize-autoloader
   php spark migrate --all
   php spark db:seed AdminSeeder
   php spark db:seed RuaCatalogueSeeder
   php spark optimize
   ```

5. Make `writable/` and `public/uploads/` writable by the web server user, and nothing else.
6. Set PHP's `post_max_size` to at least `48M` and `upload_max_filesize` to at least `5M`, so eight full-size photos can be sent at once. With a lower limit the admin form still works, but asks for smaller batches.
7. Back up the database and `public/uploads/`, and test restoring from that backup.

## Not included yet

Online payments, order and password reset emails, returns, and per-region tax or delivery rates.
#   e c o m _ a j m i  
 
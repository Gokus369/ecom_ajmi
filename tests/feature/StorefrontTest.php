<?php

namespace Tests\Feature;

use App\Models\CategoryModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Store;
use Tests\Support\Traits\StoreFixtures;

final class StorefrontTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use StoreFixtures;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = null;

    public function testHomeShowsPublishedProductsOnly(): void
    {
        $this->makeProduct(['name' => 'Visible Mug']);
        $this->makeProduct(['name' => 'Hidden Mug', 'is_published' => 0]);

        $result = $this->get('/');

        $result->assertOK();
        $result->assertSee('Visible Mug');
        $result->assertDontSee('Hidden Mug');
    }

    public function testEmptyStoreStillRenders(): void
    {
        $this->get('/')->assertSee('The new collection is on its way');
        $this->get('shop')->assertSee('Nothing here yet');
    }

    public function testHomeShowsEachCollectionWithAPhoto(): void
    {
        $garden = $this->makeCategory('Garden Party');
        $empty  = $this->makeCategory('Empty Collection');

        $this->makeProduct(['name' => 'Older', 'category_id' => $garden, 'image' => 'older.webp']);
        $this->makeProduct(['name' => 'No Photo', 'category_id' => $garden]);

        $result = $this->get('/');

        $result->assertSee('Garden Party');
        $result->assertSee('Empty Collection');
        $result->assertSee('uploads/products/thumb/older.webp');
    }

    public function testACollectionCoverBeatsAProductPhoto(): void
    {
        $category = $this->makeCategory('Test Collection');
        $cover    = FCPATH . 'assets/collections/test-collection.webp';

        $this->makeProduct(['category_id' => $category, 'image' => 'sample.webp']);

        touch($cover);

        try {
            $body = (string) $this->get('/')->getBody();
        } finally {
            unlink($cover);
        }

        $start = strpos($body, 'collection-grid');
        $grid  = substr($body, $start, strpos($body, 'class="band"') - $start);

        $this->assertStringContainsString('assets/collections/test-collection.webp', $grid);
        $this->assertStringNotContainsString('uploads/products/thumb/sample.webp', $grid);
    }

    public function testBrowsingPagesAreMarkedForScrollMotion(): void
    {
        $category = $this->makeCategory('Bridal Couture');
        $product  = $this->makeProduct(['category_id' => $category, 'image' => 'cover.webp']);

        foreach (['/', 'shop', 'bespoke', "products/{$product->slug}"] as $uri) {
            $this->assertStringContainsString('data-reveal', (string) $this->get($uri)->getBody());
        }
    }

    public function testRevealedContentIsNeverHiddenByTheMarkup(): void
    {
        $this->makeProduct();

        $body = (string) $this->get('shop')->getBody();

        // Only app.js may add this, so the page is complete without JavaScript.
        $this->assertStringNotContainsString('is-waiting', $body);
    }

    public function testBuyingPagesStayFreeOfScrollMotion(): void
    {
        $product = $this->makeProduct();
        $page    = (string) $this->withSession(['cart' => [$product->id => 1]])->get('cart')->getBody();

        $main = substr($page, strpos($page, '<main'), strpos($page, '</main>') - strpos($page, '<main'));

        $this->assertStringNotContainsString('data-reveal', $main);
    }

    public function testMotionFollowsTheVisitorUnlessSetToAlways(): void
    {
        $this->assertStringContainsString('data-motion="respect"', (string) $this->get('/')->getBody());

        config(Store::class)->motion = 'always';

        $this->assertStringContainsString('data-motion="always"', (string) $this->get('/')->getBody());
    }

    public function testHomeCarriesTheFilmWithEveryFrameCounted(): void
    {
        $body = (string) $this->get('/')->getBody();

        $this->assertSame(1, preg_match('/data-frames=(["\'])(.+?)\1/', $body, $match));

        $sets = json_decode(html_entity_decode($match[2]), true);

        $this->assertSame(['desktop', 'mobile'], array_keys($sets));

        foreach (['desktop' => 'rua-scroll-frames-4k-webp/webp', 'mobile' => 'rua-scroll-frames-mobile/webp'] as $name => $folder) {
            $this->assertSame(211, $sets[$name]['count']);
            $this->assertCount(211, glob(FCPATH . "assets/{$folder}/frame_*.webp"));
            $this->assertStringEndsWith("assets/{$folder}", $sets[$name]['url']);
        }

        $this->assertSame([3840, 2160], [$sets['desktop']['width'], $sets['desktop']['height']]);
        $this->assertSame([1280, 720], [$sets['mobile']['width'], $sets['mobile']['height']]);
        $this->assertStringNotContainsString('data-film-metres', $body);

        $this->assertStringContainsString('js/scroll-film.js', $body);
    }

    public function testPhonesStartOnTheLightFirstFrame(): void
    {
        $this->assertSame(1, preg_match(
            '#<source media="\(max-width: 768px\), \(max-height: 500px\)" srcset="[^"]*rua-scroll-frames-mobile/webp/frame_0001\.webp#',
            html_entity_decode((string) $this->get('/')->getBody()),
        ));
    }

    public function testTheFilmIsSwitchedOnBeforeTheFirstPaint(): void
    {
        $body = (string) $this->get('/')->getBody();

        $this->assertLessThan(strpos($body, 'data-film'), strpos($body, "classList.add('has-film')"));
    }

    public function testTheHeroStaysInThePageForWhenTheFilmCannotPlay(): void
    {
        $category = $this->makeCategory('Bridal Couture');

        $this->makeProduct(['category_id' => $category, 'image' => 'cover.webp']);

        $body = (string) $this->get('/')->getBody();

        $this->assertStringContainsString('class="hero"', $body);
        $this->assertStringContainsString('hero-mosaic', $body);
    }

    public function testBrandNameAndTaglineAreOnThePage(): void
    {
        $result = $this->get('/');

        $result->assertSee('RUA by Ajmi Anzari');
        $result->assertSee('Custom styles that speak your soul');
    }

    public function testEnquiryOnlyPieceHidesPriceAndCart(): void
    {
        $product = $this->makeProduct(['name' => 'Bridal Saree', 'price' => 12345600, 'stock' => 0, 'is_enquiry_only' => 1]);

        $page = $this->get("products/{$product->slug}");

        $page->assertSee('Price on request');
        $page->assertSee('Made to order');
        $page->assertSee('Enquire on WhatsApp');
        $page->assertDontSee('Add to cart');
        $page->assertDontSee('Sold out');
        $page->assertDontSee('1,23,456');
        $page->assertDontSee('123,456');

        $listing = $this->get('shop');

        $listing->assertSee('Price on request');
        $listing->assertDontSee('123,456');
        $listing->assertDontSee('Sold out');
    }

    public function testEnquiryOnlyPiecesSortAfterPricedOnes(): void
    {
        $this->makeProduct(['name' => 'Bridal Piece', 'price' => 0, 'is_enquiry_only' => 1]);
        $this->makeProduct(['name' => 'Priced Piece', 'price' => 500000]);

        $body = (string) $this->get('shop?sort=price-asc')->getBody();

        $this->assertLessThan(strpos($body, 'Bridal Piece'), strpos($body, 'Priced Piece'));
    }

    public function testBuyablePieceOffersBothCartAndWhatsApp(): void
    {
        $product = $this->makeProduct(['name' => 'Kasavu Saree']);

        $page = $this->get("products/{$product->slug}");

        $page->assertSee('Add to cart');
        $page->assertSee('wa.me/916282876142');
        $page->assertSee(rawurlencode('Kasavu Saree'));
    }

    public function testTheHomePageClosesWithAConsultationSection(): void
    {
        $page = $this->get('/');

        $page->assertSee('id="consultation-heading"');
        $page->assertSee('Book a consultation');
    }

    public function testNoPageHasTheOldBannerAboveTheHeader(): void
    {
        $product = $this->makeProduct();

        foreach (['/', 'shop', 'bespoke', 'cart', "products/{$product->slug}"] as $uri) {
            $body = (string) $this->get($uri)->getBody();

            $this->assertStringNotContainsString('Book a consultation', strstr($body, '<header', true));
        }

        $this->get('shop')->assertDontSee('Book a consultation');
    }

    public function testBespokePageOffersWhatsAppAndInstagram(): void
    {
        $page = $this->get('bespoke');

        $page->assertOK();
        $page->assertSee('Bridal consultation');
        $page->assertSee('wa.me/916282876142');
        $page->assertSee('instagram.com/rua_by_ajmianzari');
    }

    public function testBoutiqueDetailsAreOnThePage(): void
    {
        $page = $this->get('bespoke');

        $page->assertSee('Visit the boutique');
        $page->assertSee('BCG Spring Villa, 18, Dr Ambedkar Road');
        $page->assertSee('tel:+919847654918');
        $page->assertSee('google.com/maps/search');
    }

    public function testGoogleGetsTheBoutiqueDetails(): void
    {
        $body = (string) $this->get('/')->getBody();

        $this->assertSame(1, preg_match('#application/ld\+json[^>]*>(.+?)</script>#s', $body, $match));

        $details = json_decode($match[1], true);

        $this->assertSame('ClothingStore', $details['@type']);
        $this->assertSame('+91 98476 54918', $details['telephone']);
        $this->assertStringContainsString('Vennala, Kochi', $details['address']);
    }

    public function testBoutiqueDetailsDisappearWhenNotConfigured(): void
    {
        $store = config(Store::class);

        $store->address = '';
        $store->phone   = '';

        $page = $this->get('bespoke');

        $page->assertOK();
        $page->assertDontSee('Visit the boutique');
        $page->assertDontSee('google.com/maps');
        $page->assertDontSee('tel:');
    }

    public function testContactButtonsDisappearWhenNotConfigured(): void
    {
        $store = config(Store::class);

        $store->whatsapp  = '';
        $store->instagram = '';

        $product = $this->makeProduct(['is_enquiry_only' => 1]);

        foreach (['/', 'bespoke', "products/{$product->slug}"] as $uri) {
            $page = $this->get($uri);

            $page->assertOK();
            $page->assertDontSee('wa.me');
            $page->assertDontSee('instagram.com');
        }
    }

    public function testHiddenAndDeletedProductsAreNotFound(): void
    {
        $hidden  = $this->makeProduct(['name' => 'Hidden Mug', 'is_published' => 0]);
        $deleted = $this->makeProduct(['name' => 'Deleted Mug']);

        model(\App\Models\ProductModel::class)->delete($deleted->id);

        $this->get("products/{$hidden->slug}")->assertStatus(404);
        $this->get("products/{$deleted->slug}")->assertStatus(404);
    }

    public function testCategoryPageFiltersProducts(): void
    {
        $kitchen = $this->makeCategory('Kitchen');
        $home    = $this->makeCategory('Home');

        $this->makeProduct(['name' => 'Kitchen Mug', 'category_id' => $kitchen]);
        $this->makeProduct(['name' => 'Home Cushion', 'category_id' => $home]);

        $result = $this->get('shop/kitchen');

        $result->assertSee('Kitchen Mug');
        $result->assertDontSee('Home Cushion');
    }

    public function testUnknownCategoryIsNotFound(): void
    {
        $this->get('shop/does-not-exist')->assertStatus(404);
    }

    public function testSearchMatchesNameAndSummary(): void
    {
        $this->makeProduct(['name' => 'Blue Mug']);
        $this->makeProduct(['name' => 'Cushion', 'summary' => 'Goes with a blue sofa']);
        $this->makeProduct(['name' => 'Candle']);

        $result = $this->get('shop?q=blue');

        $result->assertSee('Blue Mug');
        $result->assertSee('Cushion');
        $result->assertDontSee('Candle');
    }

    public function testSearchWithNoMatchesExplainsItself(): void
    {
        $this->makeProduct();

        $this->get('shop?q=zzz')->assertSee('Nothing matched');
    }

    public function testSearchIsSafeAgainstInjectionAndOddInput(): void
    {
        $this->makeProduct(['name' => 'Blue Mug']);

        $this->get('shop?q=' . urlencode("' OR 1=1 -- "))->assertDontSee('Blue Mug');
        $this->get('shop?q[]=x')->assertOK();
    }

    public function testUnknownSortFallsBackToNewest(): void
    {
        $this->makeProduct();

        $this->get('shop?sort=password_hash')->assertOK();
    }

    public function testCardsOfferAddToCartForAPlainPiece(): void
    {
        $product = $this->makeProduct(['name' => 'Plain Piece']);
        $body    = html_entity_decode($this->get('shop')->response()->getBody());

        $this->assertStringContainsString('Add to cart', $body);
        $this->assertStringContainsString('name="product_id" value="' . $product->id . '"', $body);
    }

    public function testCardsSendPiecesWithOptionsToTheirPage(): void
    {
        $this->stockVariants($this->makeProduct(['name' => 'Silk Set']), ['' => ['S' => 5, 'M' => 5]]);

        $result = $this->get('shop');

        $result->assertSee('Choose options');
        $result->assertDontSee('Add to cart');
    }

    public function testCardsOfferNoCartForPiecesThatCannotBeBought(): void
    {
        $this->makeProduct(['name' => 'Sold Out Piece', 'stock' => 0]);
        $this->makeProduct(['name' => 'Bridal Piece', 'price' => 0, 'is_enquiry_only' => 1]);

        $result = $this->get('shop');

        $result->assertDontSee('Add to cart');
        $result->assertDontSee('Choose options');
    }

    public function testSortMenuMarksTheCurrentChoice(): void
    {
        $this->makeProduct(['name' => 'First Piece']);
        $this->makeProduct(['name' => 'Second Piece']);

        $result = $this->get('shop?sort=price-desc');
        $body   = html_entity_decode($result->response()->getBody());

        $result->assertSee('Price: high to low', 'summary');
        $this->assertStringContainsString('shop?sort=price-asc', $body);
        $this->assertStringContainsString('shop?sort=newest', $body);
        $this->assertSame(1, substr_count($body, 'aria-current="true"'));
    }

    public function testSortMenuKeepsTheSearch(): void
    {
        $this->makeProduct(['name' => 'Silk Saree']);
        $this->makeProduct(['name' => 'Silk Kurta']);

        $body = html_entity_decode($this->get('shop?q=Silk')->response()->getBody());

        $this->assertStringContainsString('shop?q=Silk&sort=price-asc', $body);
    }

    public function testSortMenuStaysInTheCategory(): void
    {
        $category = model(CategoryModel::class)->find($this->makeCategory('Bridal'));

        $this->makeProduct(['name' => 'First Piece', 'category_id' => $category->id]);
        $this->makeProduct(['name' => 'Second Piece', 'category_id' => $category->id]);

        $url  = url_to('shop.category', $category->slug);
        $body = html_entity_decode($this->get(parse_url($url, PHP_URL_PATH))->response()->getBody());

        $this->assertStringContainsString("{$url}?sort=price-asc", $body);
    }

    public function testNoSortMenuWithNothingToSort(): void
    {
        $this->makeProduct();

        $this->get('shop')->assertDontSee('Sort by');
    }

    public function testPriceSortOrdersResults(): void
    {
        $this->makeProduct(['name' => 'Expensive Thing', 'price' => 99900]);
        $this->makeProduct(['name' => 'Cheap Thing', 'price' => 10000]);

        $body = (string) $this->get('shop?sort=price-asc')->getBody();

        $this->assertLessThan(strpos($body, 'Expensive Thing'), strpos($body, 'Cheap Thing'));
    }

    public function testLongListsArePaginated(): void
    {
        for ($i = 1; $i <= 13; $i++) {
            $this->makeProduct(['name' => "Product {$i}"]);
        }

        $this->get('shop')->assertSee('Next', 'a');
    }

    public function testASinglePageShowsNoPager(): void
    {
        $this->makeProduct();

        $this->get('shop')->assertDontSeeElement('.pager');
    }

    public function testStoredTextIsEscaped(): void
    {
        $product = $this->makeProduct(['name' => '<script>alert(1)</script>', 'summary' => '"><img src=x>']);

        $body = (string) $this->get("products/{$product->slug}")->getBody();

        $this->assertStringNotContainsString('<script>alert(1)', $body);
        $this->assertStringNotContainsString('<img src=x>', $body);
    }

    public function testSoldOutProductCannotBeAdded(): void
    {
        $product = $this->makeProduct(['stock' => 0]);

        $result = $this->get("products/{$product->slug}");

        $result->assertSee('Sold out');
        $result->assertDontSee('Add to cart');
    }

    public function testDuplicateNamesGetTheirOwnSlugs(): void
    {
        $first  = $this->makeProduct(['name' => 'Same Name']);
        $second = $this->makeProduct(['name' => 'Same Name']);

        $this->assertSame('same-name', $first->slug);
        $this->assertSame('same-name-2', $second->slug);
    }

    public function testRenamingKeepsTheUrl(): void
    {
        $product = $this->makeProduct(['name' => 'Old Name']);

        model(\App\Models\ProductModel::class)->update($product->id, ['name' => 'New Name']);

        $this->get('products/old-name')->assertSee('New Name');
    }
}

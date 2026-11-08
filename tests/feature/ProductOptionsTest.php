<?php

namespace Tests\Feature;

use App\Entities\Order;
use App\Entities\Product;
use App\Exceptions\OutOfStock;
use App\Models\ColourModel;
use App\Models\ProductModel;
use App\Models\ProductVariantModel;
use App\Models\SizeModel;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;
use Tests\Support\Traits\StoreFixtures;

final class ProductOptionsTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use StoreFixtures;

    private const FULL = ['Ivory' => ['S' => 5, 'M' => 5], 'Wine' => ['S' => 5, 'M' => 5]];

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = null;

    protected function setUp(): void
    {
        parent::setUp();

        // The rate limiter would otherwise carry its count from one test into the next.
        Services::resetSingle('throttler');

        // An earlier test can leave an admin signed in, which would hide a missing guard.
        auth()->logout();
    }

    protected function tearDown(): void
    {
        auth()->logout();

        parent::tearDown();
    }

    /**
     * @param array<int|string, array<int|string, int>> $stock
     * @param array<string, mixed>                      $overrides
     */
    private function makeDress(array $stock = self::FULL, array $overrides = []): Product
    {
        return $this->stockVariants($this->makeProduct($overrides), $stock);
    }

    private function line(Product $product, string $colour = 'Ivory', string $size = 'M'): int|string
    {
        return service('cart')->key($product->id, $colour, $size);
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function saveProduct(array $fields, string $uri = 'admin/products')
    {
        // A browser only ever sends text.
        array_walk_recursive($fields, static function (mixed &$value): void {
            $value = (string) $value;
        });

        return $this->signInAs('admin')->submit($uri, [
            'name'         => 'Silk Set',
            'price'        => '100',
            'stock'        => '1',
            'is_published' => '1',
            ...$fields,
        ]);
    }

    private function savedPiece(string $name = 'Silk Set'): Product
    {
        return model(ProductModel::class)->where('name', $name)->first();
    }

    public function testEveryInstallStartsWithStandardLists(): void
    {
        $sizes = array_column(model(SizeModel::class)->ordered()->findAll(), 'name');

        $this->assertSame(['XS', 'S', 'M', 'L', 'XL', 'XXL'], $sizes);
        $this->assertNotEmpty(model(ColourModel::class)->findAll());
    }

    public function testTickingColoursAndSizesGivesEachCombinationItsOwnStock(): void
    {
        $ivory = $this->colourId('Ivory');
        $wine  = $this->colourId('Wine');
        $small = $this->sizeId('S');

        $this->saveProduct([
            'colours'       => [$ivory, $wine],
            'sizes'         => [$small],
            'variant_stock' => [$ivory => [$small => '2'], $wine => [$small => '3']],
        ]);

        $piece = $this->savedPiece();

        $this->assertSame(2, $piece->stockOf('Ivory', 'S'));
        $this->assertSame(3, $piece->stockOf('Wine', 'S'));
        $this->assertSame(5, $piece->stock);
    }

    public function testStockIsNotNeededForAPieceWithCombinations(): void
    {
        $ivory = $this->colourId('Ivory');

        $this->saveProduct(['stock' => '', 'colours' => [$ivory], 'variant_stock' => [$ivory => [0 => '4']]]);

        $this->assertSame(4, $this->savedPiece()->stockOf('Ivory', ''));
    }

    public function testAPieceWithNoCombinationsStillNeedsAStockNumber(): void
    {
        $this->saveProduct(['stock' => ''])->assertRedirectTo('admin/products/new');

        $this->assertArrayHasKey('stock', session('errors'));
    }

    public function testABlankStockBoxMeansNone(): void
    {
        $ivory = $this->colourId('Ivory');
        $small = $this->sizeId('S');

        $this->saveProduct(['colours' => [$ivory], 'sizes' => [$small], 'variant_stock' => [$ivory => [$small => '']]]);

        $this->assertSame(0, $this->savedPiece()->stockOf('Ivory', 'S'));
    }

    public function testStockForUntickedCombinationsIsIgnored(): void
    {
        $ivory = $this->colourId('Ivory');
        $wine  = $this->colourId('Wine');

        $this->saveProduct(['colours' => [$ivory], 'variant_stock' => [$ivory => [0 => '2'], $wine => [0 => '9']]]);

        $piece = $this->savedPiece();

        $this->assertSame(['Ivory'], $piece->colour_list);
        $this->assertSame(2, $piece->stock);
    }

    public function testColoursThatDoNotExistAreIgnored(): void
    {
        $this->saveProduct(['colours' => [99999], 'variant_stock' => [99999 => [0 => '9']]]);

        $this->assertSame([], $this->savedPiece()->variants);
    }

    public function testBadStockNumbersAreRefused(): void
    {
        $ivory = $this->colourId('Ivory');

        $this->saveProduct(['colours' => [$ivory], 'variant_stock' => [$ivory => [0 => 'lots']]])
            ->assertRedirectTo('admin/products/new');

        $this->assertArrayHasKey('variant_stock', session('errors'));
        $this->dontSeeInDatabase('products', ['name' => 'Silk Set']);
    }

    public function testSavingKeepsTheSameCombinationsAndChangesTheirStock(): void
    {
        $dress  = $this->makeDress(['Ivory' => ['S' => 1]]);
        $before = $dress->variantFor('Ivory', 'S')->id;
        $ivory  = $this->colourId('Ivory');
        $small  = $this->sizeId('S');

        $this->saveProduct(['colours' => [$ivory], 'sizes' => [$small], 'variant_stock' => [$ivory => [$small => '7']]], "admin/products/{$dress->id}");

        $saved = model(ProductModel::class)->find($dress->id);

        $this->assertSame($before, $saved->variantFor('Ivory', 'S')->id);
        $this->assertSame(7, $saved->stock);
    }

    public function testUntickingRemovesThoseCombinations(): void
    {
        $dress = $this->makeDress();
        $ivory = $this->colourId('Ivory');
        $small = $this->sizeId('S');

        $this->saveProduct(['colours' => [$ivory], 'sizes' => [$small], 'variant_stock' => [$ivory => [$small => '3']]], "admin/products/{$dress->id}");

        $saved = model(ProductModel::class)->find($dress->id);

        $this->assertCount(1, $saved->variants);
        $this->assertSame(3, $saved->stock);
    }

    public function testUntickingEverythingMakesItAPlainPieceAgain(): void
    {
        $dress = $this->makeDress();

        $this->saveProduct(['stock' => '6'], "admin/products/{$dress->id}");

        $saved = model(ProductModel::class)->find($dress->id);

        $this->assertSame([], $saved->variants);
        $this->assertSame(6, $saved->stock);
    }

    public function testAdminFormShowsTheSavedChoicesAndStock(): void
    {
        $dress = $this->makeDress(['Ivory' => ['S' => 4]]);
        $ivory = $this->colourId('Ivory');
        $small = $this->sizeId('S');

        $page = $this->signInAs('admin')->get("admin/products/{$dress->id}/edit");
        $body = html_entity_decode((string) $page->getBody());

        $this->assertMatchesRegularExpression('/name="colours\[\]" value="' . $ivory . '"[^>]*checked/', $body);
        $this->assertMatchesRegularExpression('/name="sizes\[\]" value="' . $small . '"[^>]*checked/', $body);
        $this->assertMatchesRegularExpression('/name="variant_stock\[' . $ivory . '\]\[' . $small . '\]"[^>]*value="4"/', $body);
    }

    public function testAFailedSaveKeepsWhatWasTickedAndTyped(): void
    {
        $ivory = $this->colourId('Ivory');
        $small = $this->sizeId('S');

        $this->saveProduct(['name' => '', 'colours' => [$ivory], 'sizes' => [$small], 'variant_stock' => [$ivory => [$small => '8']]]);

        $body = html_entity_decode((string) $this->signInAs('admin')->get('admin/products/new')->getBody());

        $this->assertMatchesRegularExpression('/name="colours\[\]" value="' . $ivory . '"[^>]*checked/', $body);
        $this->assertMatchesRegularExpression('/name="variant_stock\[' . $ivory . '\]\[' . $small . '\]"[^>]*value="8"/', $body);
    }

    public function testAdminCanAddAColourAndASize(): void
    {
        $this->signInAs('admin')->submit('admin/options/colours', ['colours_name' => 'Peacock', 'colours_hex' => '#0f5c6e', 'colours_sort_order' => '3']);
        $this->signInAs('admin')->submit('admin/options/sizes', ['sizes_name' => '3XL', 'sizes_sort_order' => '9']);

        $this->seeInDatabase('colours', ['name' => 'Peacock', 'hex' => '#0f5c6e']);
        $this->seeInDatabase('sizes', ['name' => '3XL']);
    }

    public function testRenamingAColourChangesItOnEveryPiece(): void
    {
        $dress = $this->makeDress(['Ivory' => ['S' => 1]]);
        $id    = $this->colourId('Ivory');

        $this->signInAs('admin')->submit("admin/options/colours/{$id}", ['colours_name' => 'Pearl', 'colours_hex' => '#fafafa', 'colours_sort_order' => '1']);

        $this->assertSame(['Pearl'], model(ProductModel::class)->find($dress->id)->colour_list);
    }

    public function testRepeatedNamesAreRefused(): void
    {
        $this->signInAs('admin')->submit('admin/options/colours', ['colours_name' => 'Ivory', 'colours_hex' => '#ffffff']);

        $this->assertArrayHasKey('colours_name', session('errors'));
        $this->assertSame(1, model(ColourModel::class)->where('name', 'Ivory')->countAllResults());
    }

    public function testAColourCanKeepItsOwnNameWhenSaved(): void
    {
        $id = $this->colourId('Ivory');

        $this->signInAs('admin')->submit("admin/options/colours/{$id}", ['colours_name' => 'Ivory', 'colours_hex' => '#eeeeee', 'colours_sort_order' => '1']);

        $this->seeInDatabase('colours', ['id' => $id, 'hex' => '#eeeeee']);
    }

    public function testASwatchMustBeARealColour(): void
    {
        $this->signInAs('admin')->submit('admin/options/colours', ['colours_name' => 'Bad', 'colours_hex' => 'red;background:url(x)']);

        $this->dontSeeInDatabase('colours', ['name' => 'Bad']);
    }

    public function testAColourInUseCannotBeDeleted(): void
    {
        $this->makeDress(['Ivory' => ['S' => 1]]);
        $id = $this->colourId('Ivory');

        $this->signInAs('admin')->submit("admin/options/colours/{$id}/delete")->assertSessionHas('error');

        $this->seeInDatabase('colours', ['id' => $id]);
    }

    public function testAColourNobodyUsesCanBeDeleted(): void
    {
        $id = $this->colourId('Unused');

        $this->signInAs('admin')->submit("admin/options/colours/{$id}/delete");

        $this->dontSeeInDatabase('colours', ['id' => $id]);
    }

    public function testDeletingAPieceFreesItsColours(): void
    {
        $dress = $this->makeDress(['Ivory' => ['S' => 1]]);
        $id    = $this->colourId('Ivory');

        $this->signInAs('admin')->submit("admin/products/{$dress->id}/delete");
        $this->signInAs('admin')->submit("admin/options/colours/{$id}/delete");

        $this->dontSeeInDatabase('colours', ['id' => $id]);
    }

    public function testTheListsAreForAdminsOnly(): void
    {
        $this->get('admin/options')->assertRedirectTo('login');
        $this->submit('admin/options/colours', ['colours_name' => 'Sneaky', 'colours_hex' => '#000000'])->assertRedirectTo('login');

        $this->dontSeeInDatabase('colours', ['name' => 'Sneaky']);
    }

    public function testTheListsPageShowsBothLists(): void
    {
        $page = $this->signInAs('admin')->get('admin/options');

        $page->assertOK();
        $page->assertSee('Ivory');
        $page->assertSee('XXL');
        $this->signInAs('admin')->get('admin')->assertSee('admin/options');
    }

    public function testProductPageOffersItsColoursAndSizes(): void
    {
        $dress = $this->makeDress();

        $result = $this->get("products/{$dress->slug}");
        $body   = $result->response()->getBody();

        $result->assertSee('Ivory');
        $result->assertSee('Wine');
        $this->assertStringContainsString('name="colour"', $body);
        $this->assertStringContainsString('name="size"', $body);
    }

    public function testColoursShowTheirSwatch(): void
    {
        $dress = $this->makeDress(['Ivory' => ['S' => 1]]);
        $hex   = model(ColourModel::class)->find($this->colourId('Ivory'))->hex;

        $this->assertStringContainsString("background-color:{$hex}", html_entity_decode((string) $this->get("products/{$dress->slug}")->getBody()));
    }

    public function testSizesFollowTheListNotTheOrderTheyWereAdded(): void
    {
        $dress = $this->makeDress(['Ivory' => ['XL' => 1, 'S' => 1, 'M' => 1]]);

        $this->assertSame(['S', 'M', 'XL'], $dress->size_list);
    }

    public function testThePageKnowsWhatEachCombinationHasLeft(): void
    {
        $dress = $this->makeDress(['Ivory' => ['S' => 2], 'Wine' => ['S' => 0]]);

        $body = html_entity_decode((string) $this->get("products/{$dress->slug}")->response()->getBody());

        preg_match('/data-variant-stock="(\{.*?\})"/', $body, $match);

        $this->assertSame(['Ivory' => ['S' => 2], 'Wine' => ['S' => 0]], json_decode($match[1], true));
    }

    public function testAQuoteInAColourNameCannotBreakOutOfTheStockData(): void
    {
        $dress = $this->makeDress(["Tom's' onclick='alert(1)" => ['S' => 1]]);

        $body = (string) $this->get("products/{$dress->slug}")->response()->getBody();

        $this->assertStringNotContainsString("onclick='alert", $body);
        $this->assertStringContainsString('data-variant-stock="', $body);
    }

    public function testASizeSoldOutInEveryColourCannotBePicked(): void
    {
        $dress = $this->makeDress(['Ivory' => ['S' => 0, 'M' => 1], 'Wine' => ['S' => 0, 'M' => 1]]);

        $body = (string) $this->get("products/{$dress->slug}")->getBody();

        $this->assertMatchesRegularExpression('/name="size" value="S"[^>]*disabled/', $body);
        $this->assertDoesNotMatchRegularExpression('/name="size" value="M"[^>]*disabled/', $body);
    }

    public function testAPieceWithNothingLeftInAnyCombinationIsSoldOut(): void
    {
        $dress = $this->makeDress(['Ivory' => ['S' => 0]]);

        $this->assertTrue($dress->isSoldOut());
        $this->get("products/{$dress->slug}")->assertSee('Sold out');
    }

    public function testProductsWithoutOptionsAskForNoChoice(): void
    {
        $product = $this->makeProduct();

        $body = $this->get("products/{$product->slug}")->response()->getBody();

        $this->assertStringNotContainsString('name="colour"', $body);
        $this->assertStringNotContainsString('name="size"', $body);
    }

    public function testASingleOptionIsChosenForTheShopper(): void
    {
        $dress = $this->makeDress(['Ivory' => ['S' => 5, 'M' => 5]]);

        $body = $this->get("products/{$dress->slug}")->response()->getBody();

        $this->assertSame(1, substr_count($body, ' checked'));
    }

    public function testEnquiryPiecesListTheirOptionsWithoutAChoice(): void
    {
        $dress = $this->makeDress(overrides: ['is_enquiry_only' => 1]);

        $result = $this->get("products/{$dress->slug}");

        $result->assertSee('Ivory, Wine');
        $this->assertStringNotContainsString('name="colour"', $result->response()->getBody());
    }

    public function testAddingNeedsAChoice(): void
    {
        $dress = $this->makeDress();

        $this->submit('cart/add', ['product_id' => $dress->id])->assertSessionHas('error');
        $this->submit('cart/add', ['product_id' => $dress->id, 'colour' => 'Ivory'])->assertSessionHas('error');

        $this->assertEmpty(session('cart'));
    }

    public function testOptionsThePieceDoesNotComeInAreRefused(): void
    {
        $dress = $this->makeDress();

        $this->submit('cart/add', ['product_id' => $dress->id, 'colour' => 'Emerald', 'size' => 'M'])->assertSessionHas('error');
        $this->submit('cart/add', ['product_id' => $dress->id, 'colour' => ['Ivory'], 'size' => 'M'])->assertSessionHas('error');

        $this->assertEmpty(session('cart'));
    }

    public function testAProductWithOnlySizesNeedsOnlyASize(): void
    {
        $dress = $this->makeDress(['' => ['S' => 5, 'M' => 5]]);

        $this->submit('cart/add', ['product_id' => $dress->id, 'size' => 'M'])->assertRedirect();

        $this->assertSame([service('cart')->key($dress->id, '', 'M') => 1], session('cart'));
    }

    public function testEachChoiceIsItsOwnLine(): void
    {
        $dress = $this->makeDress();
        $ivory = $this->line($dress);
        $wine  = $this->line($dress, 'Wine');

        $this->submit('cart/add', ['product_id' => $dress->id, 'colour' => 'Ivory', 'size' => 'M']);
        $this->withSession()->submit('cart/add', ['product_id' => $dress->id, 'colour' => 'Wine', 'size' => 'M', 'quantity' => 2]);

        $this->assertSame([$ivory => 1, $wine => 2], session('cart'));

        $result = $this->withSession()->get('cart');

        $result->assertSee('Colour: Ivory');
        $result->assertSee('Colour: Wine');
        $result->assertSee('Size: M');
    }

    public function testTheSameChoiceAddsUp(): void
    {
        $dress = $this->makeDress();
        $line  = $this->line($dress);

        $this->withSession(['cart' => [$line => 1]])
            ->submit('cart/add', ['product_id' => $dress->id, 'colour' => 'Ivory', 'size' => 'M', 'quantity' => 2]);

        $this->assertSame([$line => 3], session('cart'));
    }

    public function testACombinationIsCappedAtItsOwnStock(): void
    {
        $dress = $this->makeDress(['Ivory' => ['M' => 1], 'Wine' => ['M' => 5]]);

        $result = $this->submit('cart/add', ['product_id' => $dress->id, 'colour' => 'Ivory', 'size' => 'M', 'quantity' => 3]);

        $result->assertSessionHas('warning');
        $this->assertSame([$this->line($dress) => 1], session('cart'));
    }

    public function testOneCombinationDoesNotUseUpAnother(): void
    {
        $dress = $this->makeDress(['Ivory' => ['M' => 2], 'Wine' => ['M' => 2]]);
        $ivory = $this->line($dress);
        $wine  = $this->line($dress, 'Wine');

        $result = $this->withSession(['cart' => [$ivory => 2]])
            ->submit('cart/add', ['product_id' => $dress->id, 'colour' => 'Wine', 'size' => 'M', 'quantity' => 2]);

        $result->assertSessionHas('message');
        $this->assertSame([$ivory => 2, $wine => 2], session('cart'));
    }

    public function testASoldOutCombinationCannotBeAdded(): void
    {
        $dress = $this->makeDress(['Ivory' => ['M' => 0], 'Wine' => ['M' => 3]]);

        $this->submit('cart/add', ['product_id' => $dress->id, 'colour' => 'Ivory', 'size' => 'M'])->assertSessionHas('error');

        $this->assertEmpty(session('cart'));
    }

    public function testChangingAQuantityIsCappedAtTheCombination(): void
    {
        $dress = $this->makeDress(['Ivory' => ['M' => 2], 'Wine' => ['M' => 1]]);
        $ivory = $this->line($dress);
        $wine  = $this->line($dress, 'Wine');

        $result = $this->withSession(['cart' => [$ivory => 2, $wine => 1]])
            ->submit("cart/update/{$dress->id}", ['colour' => 'Wine', 'size' => 'M', 'quantity' => 3]);

        $result->assertSessionHas('warning');
        $this->assertSame([$ivory => 2, $wine => 1], session('cart'));
    }

    public function testCartShrinksALineToFitItsCombination(): void
    {
        $dress = $this->makeDress(['Ivory' => ['M' => 2], 'Wine' => ['M' => 1]]);
        $ivory = $this->line($dress);
        $wine  = $this->line($dress, 'Wine');

        $result = $this->withSession(['cart' => [$ivory => 2, $wine => 2]])->get('cart');

        $result->assertSee('Only 1 of Stoneware Mug (Wine, M) left');
        $this->assertSame([$ivory => 2, $wine => 1], session('cart'));
    }

    public function testALineDropsWhenItsCombinationSellsOut(): void
    {
        $dress = $this->makeDress(['Ivory' => ['M' => 0], 'Wine' => ['M' => 2]]);

        $result = $this->withSession(['cart' => [$this->line($dress) => 1]])->get('cart');

        $result->assertSee('Stoneware Mug (Ivory, M) just sold out');
        $this->assertEmpty(session('cart'));
    }

    public function testOnlyAFullLineStopsOfferingMore(): void
    {
        $dress = $this->makeDress(['Ivory' => ['M' => 2], 'Wine' => ['M' => 3]]);

        $body = $this->withSession(['cart' => [$this->line($dress) => 2, $this->line($dress, 'Wine') => 1]])->get('cart')->response()->getBody();

        $this->assertSame(1, preg_match_all('/aria-label="One more[^>]*disabled/', $body));
    }

    public function testChangingAQuantityTouchesOneLine(): void
    {
        $dress = $this->makeDress();
        $ivory = $this->line($dress);
        $wine  = $this->line($dress, 'Wine');

        $this->withSession(['cart' => [$ivory => 1, $wine => 1]])
            ->submit("cart/update/{$dress->id}", ['colour' => 'Wine', 'size' => 'M', 'quantity' => 2]);

        $this->assertSame([$ivory => 1, $wine => 2], session('cart'));
    }

    public function testRemovingTakesOutOneLine(): void
    {
        $dress = $this->makeDress();
        $ivory = $this->line($dress);
        $wine  = $this->line($dress, 'Wine');

        $this->withSession(['cart' => [$ivory => 1, $wine => 1]])
            ->submit("cart/remove/{$dress->id}", ['colour' => 'Ivory', 'size' => 'M']);

        $this->assertSame([$wine => 1], session('cart'));
    }

    public function testALineDropsWhenItsOptionIsWithdrawn(): void
    {
        $dress = $this->makeDress();
        $line  = $this->line($dress);

        $this->stockVariants($dress, ['Wine' => ['M' => 5]]);

        $result = $this->withSession(['cart' => [$line => 1]])->get('cart');

        $result->assertSee('Stoneware Mug (Ivory, M) has changed');
        $this->assertEmpty(session('cart'));
    }

    public function testAnUnchosenLineDropsWhenOptionsAreAdded(): void
    {
        $product = $this->makeProduct();

        $this->stockVariants($product, ['' => ['S' => 5]]);

        $result = $this->withSession(['cart' => [$product->id => 1]])->get('cart');

        $result->assertSee('Stoneware Mug has changed');
        $this->assertEmpty(session('cart'));
    }

    public function testCheckoutShowsTheChoice(): void
    {
        $dress = $this->makeDress();

        $this->withSession(['cart' => [$this->line($dress) => 1]])->get('checkout')->assertSee('Colour: Ivory');
    }

    public function testAnOrderTakesStockFromTheRightCombinations(): void
    {
        $dress = $this->makeDress(['Ivory' => ['M' => 3], 'Wine' => ['M' => 3]], ['price' => 50000]);

        $this->withSession(['cart' => [$this->line($dress) => 1, $this->line($dress, 'Wine') => 2]])
            ->submit('checkout', [...$this->customer(), 'expected_total' => 150000]);

        $order = $this->latestOrder();

        $this->seeInDatabase('order_items', ['order_id' => $order->id, 'colour' => 'Ivory', 'size' => 'M', 'quantity' => 1]);
        $this->seeInDatabase('order_items', ['order_id' => $order->id, 'colour' => 'Wine', 'size' => 'M', 'quantity' => 2]);
        $this->assertSame(2, $this->variantStock($dress, 'Ivory', 'M'));
        $this->assertSame(1, $this->variantStock($dress, 'Wine', 'M'));
        $this->assertSame(3, $this->stockOf($dress));
    }

    public function testAnOrderKeepsItsWordingWhenTheListChanges(): void
    {
        $dress = $this->makeDress(['Wine' => ['M' => 5]], ['price' => 50000]);

        $this->withSession(['cart' => [$this->line($dress, 'Wine') => 1]])
            ->submit('checkout', [...$this->customer(), 'expected_total' => 50000 + 5000]);

        $order = $this->latestOrder();

        // Past orders are a record, so a later rename must not rewrite them.
        model(ColourModel::class)->update($this->colourId('Wine'), ['name' => 'Burgundy']);

        $this->get("orders/{$order->number}")->assertSee('Colour: Wine');
        $this->signInAs('admin')->get("admin/orders/{$order->id}")->assertSee('Colour: Wine');
    }

    public function testACombinationThatRunsOutAtCheckoutStopsTheOrder(): void
    {
        $dress = $this->makeDress(['Ivory' => ['M' => 1]]);
        $line  = ['product' => $dress, 'variant_id' => $dress->variantFor('Ivory', 'M')->id, 'colour' => 'Ivory', 'size' => 'M', 'quantity' => 2, 'total' => 1];

        $this->expectException(OutOfStock::class);

        service('orders')->place($this->customer(['notes' => null]), [$line], ['subtotal' => 1, 'shipping' => 0, 'total' => 1]);
    }

    public function testAFailedOrderLeavesEveryCombinationAlone(): void
    {
        $dress = $this->makeDress(['Ivory' => ['M' => 2], 'Wine' => ['M' => 1]]);
        $line  = static fn (string $colour, int $quantity): array => ['product' => $dress, 'variant_id' => $dress->variantFor($colour, 'M')->id, 'colour' => $colour, 'size' => 'M', 'quantity' => $quantity, 'total' => 1];

        try {
            service('orders')->place($this->customer(['notes' => null]), [$line('Ivory', 1), $line('Wine', 2)], ['subtotal' => 2, 'shipping' => 0, 'total' => 2]);
            $this->fail('The order should have been refused.');
        } catch (OutOfStock) {
            $this->assertSame(2, $this->variantStock($dress, 'Ivory', 'M'));
            $this->assertSame(1, $this->variantStock($dress, 'Wine', 'M'));
            $this->assertSame(3, $this->stockOf($dress));
        }
    }

    public function testOrdersForPlainProductsRecordNoChoice(): void
    {
        $product = $this->makeProduct();

        $order = $this->placeOrder([[$product, 1]]);

        $this->seeInDatabase('order_items', ['order_id' => $order->id, 'colour' => null, 'size' => null, 'variant_id' => null]);
    }

    public function testCancellingPutsEachLineBackOnItsCombination(): void
    {
        $dress = $this->makeDress(['Ivory' => ['M' => 3], 'Wine' => ['M' => 3]], ['price' => 50000]);

        $this->withSession(['cart' => [$this->line($dress) => 1, $this->line($dress, 'Wine') => 2]])
            ->submit('checkout', [...$this->customer(), 'expected_total' => 150000]);

        service('orders')->moveTo($this->latestOrder(), Order::CANCELLED);

        $this->assertSame(3, $this->variantStock($dress, 'Ivory', 'M'));
        $this->assertSame(3, $this->variantStock($dress, 'Wine', 'M'));
        $this->assertSame(6, $this->stockOf($dress));
    }

    public function testCancellingCopesWhenTheCombinationHasGone(): void
    {
        $dress = $this->makeDress(['Ivory' => ['M' => 3], 'Wine' => ['M' => 3]], ['price' => 50000]);

        $this->withSession(['cart' => [$this->line($dress) => 1]])
            ->submit('checkout', [...$this->customer(), 'expected_total' => 50000 + 5000]);

        $this->stockVariants($dress, ['Wine' => ['M' => 3]]);

        service('orders')->moveTo($this->latestOrder(), Order::CANCELLED);

        $this->assertSame(3, $this->stockOf($dress));
    }

    public function testVariantStockAlwaysAddsUpToThePieceTotal(): void
    {
        $dress = $this->makeDress(['Ivory' => ['S' => 1, 'M' => 2], 'Wine' => ['S' => 3, 'M' => 4]]);

        $sum = array_sum(array_map(static fn ($variant): int => $variant->stock, model(ProductVariantModel::class)->where('product_id', $dress->id)->findAll()));

        $this->assertSame(10, $sum);
        $this->assertSame(10, $this->stockOf($dress));
    }

    public function testOptionNamesCannotInjectMarkup(): void
    {
        $attack = '"><script>alert(1)</script>';
        $dress  = $this->makeDress([$attack => ['M' => 5]], ['price' => 100000]);
        $line   = $this->line($dress, $attack, 'M');

        // Placed first, because each request after it renews the CSRF token.
        $this->withSession(['cart' => [$line => 1]])->submit('checkout', [...$this->customer(), 'expected_total' => 100000]);

        $order = $this->latestOrder();

        // Read straight away: every request reuses the same response object.
        $pages = [
            $this->get("products/{$dress->slug}")->response()->getBody(),
            $this->get("orders/{$order->number}")->response()->getBody(),
            $this->withSession(['cart' => [$line => 1]])->get('cart')->response()->getBody(),
            $this->withSession(['cart' => [$line => 1]])->get('checkout')->response()->getBody(),
            $this->signInAs('admin')->get("admin/products/{$dress->id}/edit")->response()->getBody(),
            $this->signInAs('admin')->get('admin/options')->response()->getBody(),
            $this->signInAs('admin')->get("admin/orders/{$order->id}")->response()->getBody(),
        ];

        foreach ($pages as $body) {
            // The first check proves the page really shows the option.
            $this->assertStringContainsString('&lt;script&gt;alert', $body);
            $this->assertStringNotContainsString('<script>alert', $body);
        }
    }

    public function testASizeOfZeroIsARealSize(): void
    {
        $dress = $this->makeDress(['' => ['0' => 2, '2' => 2]]);
        $line  = service('cart')->key($dress->id, '', '0');

        $this->assertSame(['0', '2'], $dress->size_list);

        $this->submit('cart/add', ['product_id' => $dress->id, 'size' => '0']);
        $this->assertSame([$line => 1], session('cart'));

        $this->withSession(['cart' => [$line => 1]])->get('cart')->assertSee('Size: 0');
    }
}

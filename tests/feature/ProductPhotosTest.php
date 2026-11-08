<?php

namespace Tests\Feature;

use App\Entities\Product;
use App\Libraries\ProductImages;
use App\Models\ProductModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Tests\Support\Traits\StoreFixtures;

final class ProductPhotosTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use StoreFixtures;

    private const PIECE = ['name' => 'Mug', 'price' => '10', 'stock' => '1', 'is_published' => '1'];

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = null;

    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir() . '/rua-photos-' . uniqid() . '/';

        foreach (['large', 'thumb', 'sent'] as $folder) {
            mkdir($this->root . $folder, 0777, true);
        }

        // A real upload can only be moved during a real HTTP request, so only
        // store() is replaced. Deleting still runs against real files.
        Services::injectMock('productImages', new class ($this->root) extends ProductImages {
            private int $stored = 0;

            public function __construct(private readonly string $folder)
            {
                parent::__construct($folder);
            }

            public function store(UploadedFile $file): string
            {
                if ($file->getClientName() === 'damaged.png') {
                    throw new RuntimeException('Could not decode this photo.');
                }

                $name = 'new-' . ++$this->stored . '.webp';

                touch($this->folder . 'large/' . $name);
                touch($this->folder . 'thumb/' . $name);

                return $name;
            }
        });
    }

    protected function tearDown(): void
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($this->root);

        service('superglobals')->setFilesArray([]);
        Services::resetSingle('productImages');

        parent::tearDown();
    }

    public function testTheProductPageShowsEveryPhotoWithTheMainOneFirst(): void
    {
        $product = $this->pieceWith('main.webp', 'side.webp', 'detail.webp');

        $body = (string) $this->get("products/{$product->slug}")->getBody();

        $main   = strpos($body, 'uploads/products/large/main.webp');
        $side   = strpos($body, 'uploads/products/large/side.webp');
        $detail = strpos($body, 'uploads/products/large/detail.webp');

        $this->assertNotFalse($main);
        $this->assertGreaterThan($main, $side);
        $this->assertGreaterThan($side, $detail);
        $this->assertStringContainsString('href="#photo-3"', $body);
        $this->assertStringContainsString('uploads/products/thumb/detail.webp', $body);
    }

    public function testOnePhotoHasNoThumbnails(): void
    {
        $product = $this->pieceWith('main.webp');

        $result = $this->get("products/{$product->slug}");

        $result->assertSee('uploads/products/large/main.webp');
        $result->assertDontSee('gallery-thumb');
    }

    public function testAPieceWithNoPhotosKeepsItsLetterTile(): void
    {
        $product = $this->makeProduct();

        $result = $this->get("products/{$product->slug}");

        $result->assertSee('image-fallback');
        $result->assertDontSee('gallery-thumb');
    }

    public function testShoppingPagesKeepUsingTheMainPhoto(): void
    {
        $this->pieceWith('main.webp', 'side.webp');

        $result = $this->get('shop');

        $result->assertSee('uploads/products/thumb/main.webp');
        $result->assertDontSee('side.webp');
    }

    public function testTheFormHasAPlaceToPreviewChosenPhotos(): void
    {
        $result = $this->signInAs('admin')->get('admin/products/new');

        $result->assertSee('data-previews="photo-previews"');
        $result->assertSee('id="photo-previews"');
        $result->assertSee('not saved yet');
    }

    public function testTheFormOffersEachPhotoToKeepOrRemove(): void
    {
        $product = $this->pieceWith('main.webp', 'side.webp');

        $result = $this->signInAs('admin')->get("admin/products/{$product->id}/edit");

        $result->assertSee('name="main_photo" value="main.webp" checked');
        $result->assertSee('name="main_photo" value="side.webp"');
        $result->assertSee('name="remove_photos[]" value="side.webp"');
        $result->assertSee('name="photos[]"');
        $result->assertSee('multiple');
        $result->assertSee('data-max-bytes');
    }

    public function testSeveralPhotosCanBeAddedAtOnceAndTheFirstBecomesMain(): void
    {
        $this->attach('one.png', 'two.png', 'three.png');

        $this->signInAs('admin')->submit('admin/products', self::PIECE);

        $product = model(ProductModel::class)->where('name', 'Mug')->first();

        $this->assertSame('new-1.webp', $product->image);
        $this->assertSame(['new-1.webp', 'new-2.webp', 'new-3.webp'], $this->photosOf($product));
    }

    public function testNewPhotosJoinTheOnesAlreadyThere(): void
    {
        $product = $this->pieceWith('main.webp', 'side.webp');

        $this->attach('one.png');
        $this->save($product);

        $this->assertSame(['main.webp', 'side.webp', 'new-1.webp'], $this->photosOf($product));
    }

    public function testSavingWithoutTouchingThePhotosKeepsThem(): void
    {
        $product = $this->pieceWith('main.webp', 'side.webp');

        $this->save($product, ['main_photo' => 'main.webp']);

        $this->assertSame(['main.webp', 'side.webp'], $this->photosOf($product));
    }

    public function testAnotherPhotoCanBecomeTheMainOne(): void
    {
        $product = $this->pieceWith('main.webp', 'side.webp', 'detail.webp');

        $this->save($product, ['main_photo' => 'detail.webp']);

        $this->seeInDatabase('products', ['id' => $product->id, 'image' => 'detail.webp']);
        $this->assertSame(['detail.webp', 'main.webp', 'side.webp'], $this->photosOf($product));
    }

    public function testRemovingAPhotoDeletesItsFiles(): void
    {
        $product = $this->pieceWith('main.webp', 'side.webp');

        $this->save($product, ['remove_photos' => ['side.webp']]);

        $this->assertSame(['main.webp'], $this->photosOf($product));
        $this->assertFileDoesNotExist($this->root . 'large/side.webp');
        $this->assertFileDoesNotExist($this->root . 'thumb/side.webp');
        $this->assertFileExists($this->root . 'large/main.webp');
    }

    public function testRemovingTheMainPhotoPromotesTheNextOne(): void
    {
        $product = $this->pieceWith('main.webp', 'side.webp', 'detail.webp');

        $this->save($product, ['main_photo' => 'main.webp', 'remove_photos' => ['main.webp']]);

        $this->seeInDatabase('products', ['id' => $product->id, 'image' => 'side.webp']);
        $this->assertSame(['side.webp', 'detail.webp'], $this->photosOf($product));
    }

    public function testEveryPhotoCanBeRemoved(): void
    {
        $product = $this->pieceWith('main.webp', 'side.webp');

        $this->save($product, ['remove_photos' => ['main.webp', 'side.webp']]);

        $this->seeInDatabase('products', ['id' => $product->id, 'image' => null]);
        $this->assertSame([], $this->photosOf($product));
    }

    public function testAForgedFormCannotTouchAnotherPiecesPhotos(): void
    {
        $product = $this->pieceWith('main.webp');
        $other   = $this->pieceWith('theirs.webp', 'theirs-side.webp');

        $this->save($product, ['main_photo' => 'theirs.webp', 'remove_photos' => ['theirs-side.webp', '../../index.php']]);

        $this->assertSame(['main.webp'], $this->photosOf($product));
        $this->assertSame(['theirs.webp', 'theirs-side.webp'], $this->photosOf($other));
        $this->assertFileExists($this->root . 'large/theirs-side.webp');
    }

    public function testAMalformedRemoveListIsIgnored(): void
    {
        $product = $this->pieceWith('main.webp');

        $this->save($product, ['main_photo' => ['main.webp'], 'remove_photos' => [['main.webp']]]);

        $this->assertSame(['main.webp'], $this->photosOf($product));
    }

    public function testAPieceCanReachTheLimit(): void
    {
        $product = $this->pieceWith(...$this->names(ProductImages::MAX_PER_PIECE - 1));

        $this->attach('one.png');
        $this->save($product);

        $this->assertCount(ProductImages::MAX_PER_PIECE, $this->photosOf($product));
    }

    public function testAPieceCannotGoOverTheLimit(): void
    {
        $product = $this->pieceWith(...$this->names(ProductImages::MAX_PER_PIECE));

        $this->attach('one.png');
        $result = $this->save($product, ['name' => 'Renamed']);

        $result->assertRedirect();
        $this->assertArrayHasKey('photos', $_SESSION['errors']);
        $this->assertCount(ProductImages::MAX_PER_PIECE, $this->photosOf($product));
        $this->assertFileDoesNotExist($this->root . 'large/new-1.webp');
        $this->seeInDatabase('products', ['id' => $product->id, 'name' => $product->name]);
    }

    public function testRemovingPhotosMakesRoomForNewOnesInTheSameSave(): void
    {
        $names   = $this->names(ProductImages::MAX_PER_PIECE);
        $product = $this->pieceWith(...$names);

        $this->attach('one.png');
        $this->save($product, ['remove_photos' => [$names[1]]]);

        $this->assertCount(ProductImages::MAX_PER_PIECE, $this->photosOf($product));
        $this->assertContains('new-1.webp', $this->photosOf($product));
    }

    public function testAFileThatIsNotAPhotoIsRefusedAndNothingIsSaved(): void
    {
        $product = $this->pieceWith('main.webp');

        $this->attach('one.png', 'notes.txt');
        $result = $this->save($product, ['name' => 'Renamed']);

        $result->assertRedirect();
        $this->assertArrayHasKey('photos', $_SESSION['errors']);
        $this->assertSame(['main.webp'], $this->photosOf($product));
        $this->assertFileDoesNotExist($this->root . 'large/new-1.webp');
        $this->seeInDatabase('products', ['id' => $product->id, 'name' => $product->name]);
    }

    public function testAFailedSaveKeepsThePhotoChoices(): void
    {
        $product = $this->pieceWith('main.webp', 'side.webp');

        $this->save($product, ['price' => '', 'main_photo' => 'side.webp', 'remove_photos' => ['main.webp']]);

        $result = $this->signInAs('admin')->get("admin/products/{$product->id}/edit");

        $result->assertSee('name="main_photo" value="side.webp" checked');
        $result->assertSee('name="remove_photos[]" value="main.webp" checked');
    }

    public function testADamagedPhotoLeavesNothingBehind(): void
    {
        $product = $this->pieceWith('main.webp');

        $this->attach('one.png', 'damaged.png');
        $result = $this->save($product, ['name' => 'Renamed', 'remove_photos' => ['main.webp']]);

        $result->assertRedirect();
        $this->assertArrayHasKey('photos', $_SESSION['errors']);
        $this->assertSame(['main.webp'], $this->photosOf($product));
        $this->assertFileExists($this->root . 'large/main.webp');
        $this->assertFileDoesNotExist($this->root . 'large/new-1.webp');
        $this->assertFileDoesNotExist($this->root . 'thumb/new-1.webp');
        $this->seeInDatabase('products', ['id' => $product->id, 'name' => $product->name]);
    }

    private function pieceWith(string ...$photos): Product
    {
        static $piece = 0;

        $product = $this->makeProduct(['name' => 'Stoneware Mug ' . ++$piece]);

        foreach ($photos as $photo) {
            touch($this->root . 'large/' . $photo);
            touch($this->root . 'thumb/' . $photo);
        }

        model(ProductModel::class)->setPhotos($product->id, $photos);

        return model(ProductModel::class)->find($product->id);
    }

    /**
     * @return list<string>
     */
    private function names(int $count): array
    {
        return array_map(static fn (int $number): string => "photo-{$number}.webp", range(1, $count));
    }

    /**
     * @return list<string>
     */
    private function photosOf(Product $product): array
    {
        $products = model(ProductModel::class);

        return $products->photosOf($products->find($product->id));
    }

    /**
     * @param array<string, mixed> $changes
     */
    private function save(Product $product, array $changes = [])
    {
        return $this->signInAs('admin')->submit("admin/products/{$product->id}", [
            ...self::PIECE,
            'name' => $product->name,
            ...$changes,
        ]);
    }

    /**
     * Puts real files where PHP would put them for an input named photos[].
     */
    private function attach(string ...$names): void
    {
        $files = ['name' => [], 'type' => [], 'size' => [], 'tmp_name' => [], 'error' => []];

        foreach ($names as $name) {
            $path = $this->root . 'sent/' . $name;

            if (str_ends_with($name, '.png')) {
                imagepng(imagecreatetruecolor(40, 50), $path);
            } else {
                file_put_contents($path, 'Not a photo.');
            }

            $files['name'][]     = $name;
            $files['type'][]     = mime_content_type($path);
            $files['size'][]     = filesize($path);
            $files['tmp_name'][] = $path;
            $files['error'][]    = UPLOAD_ERR_OK;
        }

        service('superglobals')->setFilesArray(['photos' => $files]);
    }
}

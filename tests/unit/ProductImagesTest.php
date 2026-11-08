<?php

namespace Tests\Unit;

use App\Libraries\ProductImages;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;

final class ProductImagesTest extends CIUnitTestCase
{
    private string $root;
    private string $photo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root  = sys_get_temp_dir() . '/rua-images-' . uniqid() . '/';
        $this->photo = $this->root . 'landscape.png';

        mkdir($this->root);

        $image = imagecreatetruecolor(1600, 900);
        imagefill($image, 0, 0, imagecolorallocate($image, 91, 18, 38));
        imagepng($image, $this->photo);
    }

    protected function tearDown(): void
    {
        foreach (array_keys(ProductImages::SIZES) as $folder) {
            array_map('unlink', glob($this->root . $folder . '/*') ?: []);

            if (is_dir($this->root . $folder)) {
                rmdir($this->root . $folder);
            }
        }

        unlink($this->photo);
        rmdir($this->root);

        parent::tearDown();
    }

    public function testPhotosBecomePortraitWebpInBothSizes(): void
    {
        $name = (new ProductImages($this->root))->store($this->upload());

        $this->assertStringEndsWith('.webp', $name);

        foreach (ProductImages::SIZES as $folder => [$width, $height]) {
            [$actualWidth, $actualHeight, $type] = getimagesize("{$this->root}{$folder}/{$name}");

            $this->assertSame([$width, $height, IMAGETYPE_WEBP], [$actualWidth, $actualHeight, $type]);
        }
    }

    public function testTheOriginalUploadIsNotKept(): void
    {
        $before = glob(WRITEPATH . 'uploads/*');

        (new ProductImages($this->root))->store($this->upload());

        $this->assertSame($before, glob(WRITEPATH . 'uploads/*'));
    }

    public function testDeletingRemovesEverySize(): void
    {
        $images = new ProductImages($this->root);
        $name   = $images->store($this->upload());

        $images->delete($name);

        foreach (array_keys(ProductImages::SIZES) as $folder) {
            $this->assertFileDoesNotExist("{$this->root}{$folder}/{$name}");
        }
    }

    /**
     * A real upload can only be moved during a real HTTP request, so this copies instead.
     */
    private function upload(): UploadedFile
    {
        return new class ($this->photo, 'landscape.png', 'image/png', 1, UPLOAD_ERR_OK) extends UploadedFile {
            public function move(string $targetPath, ?string $name = null, bool $overwrite = false)
            {
                return copy($this->getRealPath(), rtrim($targetPath, '/') . '/' . $name);
            }
        };
    }
}

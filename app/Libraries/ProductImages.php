<?php

namespace App\Libraries;

use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Turns an uploaded photo into the 4:5 portrait WebP sizes the storefront uses.
 * The original upload is thrown away, so nothing a user sent is ever served.
 */
class ProductImages
{
    /** Folder name => [width, height] in pixels */
    public const SIZES = ['large' => [1200, 1500], 'thumb' => [600, 750]];

    /** How many photos one piece can have, counting the main photo. */
    public const MAX_PER_PIECE = 8;

    private const QUALITY = 82;

    private readonly string $root;

    public function __construct(?string $root = null)
    {
        $this->root = $root ?? FCPATH . 'uploads/products/';
    }

    /**
     * @return string the new file name, for products.image
     */
    public function store(UploadedFile $file): string
    {
        $uploadDir = WRITEPATH . 'uploads/';
        $upload    = $file->getRandomName();

        $file->move($uploadDir, $upload);

        $name = pathinfo($upload, PATHINFO_FILENAME) . '.webp';

        try {
            foreach (self::SIZES as $folder => [$width, $height]) {
                if (! is_dir($this->root . $folder)) {
                    mkdir($this->root . $folder, 0755, true);
                }

                service('image')
                    ->withFile($uploadDir . $upload)
                    ->reorient(true)
                    ->fit($width, $height, 'center')
                    ->convert(IMAGETYPE_WEBP)
                    ->save($this->root . $folder . '/' . $name, self::QUALITY);
            }
        } finally {
            unlink($uploadDir . $upload);
        }

        return $name;
    }

    public function delete(?string $name): void
    {
        if ($name === null || $name === '') {
            return;
        }

        foreach (array_keys(self::SIZES) as $folder) {
            $path = $this->root . $folder . '/' . basename($name);

            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}

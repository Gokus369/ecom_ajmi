<?php

namespace App\Database\Seeds;

use App\Libraries\ProductImages;
use App\Models\CategoryModel;
use App\Models\ProductModel;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;

/**
 * Real pieces from the label's Instagram, rua_by_ajmianzari. Instagram shows no
 * prices, so every piece is enquiry only. Safe to run again: a piece that already
 * exists, even one deleted in the admin, is left alone.
 *
 * The photos in Seeds/rua are crops of Instagram covers, so replace them in the
 * admin when the brand sends the originals. The two hidden pieces use photos from
 * other people's posts, and stay hidden until those people agree to their use.
 */
class RuaCatalogueSeeder extends Seeder
{
    private const QUALITY = 82;

    /**
     * Category, name, image, summary, description, published. The shop lists the
     * newest first, so the last piece here comes first.
     */
    private const PIECES = [
        // Hidden. Photo from vishnu.mayaa's post, which could not be read, so no details are known.
        [null, 'Ivory and Gold Lehenga', 'ivory-and-gold-lehenga', 'Ivory and gold lehenga with a long sheer dupatta.', null, false],
        // Hidden. Photo from vijilsmakeover's post, whose caption gives no details of the outfit.
        [null, 'Rust Red Embroidered Outfit', 'rust-red-embroidered-outfit', 'Long rust red outfit with gold embroidered panels.', null, false],

        ['Onam Collection', 'Thechi Embroidered Blouse', 'thechi-embroidered-blouse', 'Handcrafted blouse scattered with red Thechi flowers, from the Onam Collection.', "From the Signature Thechi edit in our Onam Collection, this festive blouse is scattered with handcrafted red Thechi flowers.\n\nEach blouse is a custom piece. Message us to talk through your fit and place your order.", true],
        ['Onam Collection', 'Onathumbi Dragonfly Blouse', 'onathumbi-dragonfly-blouse', 'Ivory blouse with a beaded gold dragonfly, from the Onam Collection.', "Onathumbi is the dragonfly of Onam, picked out here in gold beadwork on ivory.\n\nEach blouse is a custom piece. Message us to talk through your fit and place your order.", true],
        ['Onam Collection', 'Onappoovu Floral Blouse', 'onappoovu-floral-blouse', 'Ivory festive blouse with a floral motif in sequins and beads, from the Onam Collection.', "A floral motif in sequins and beads blooms across this festive Onam blouse.\n\nEach blouse is a custom piece. Message us to talk through your fit and place your order.", true],
        ['Kurta Sets', 'Rosewood Chanderi Kurta Set', 'rosewood-chanderi-kurta-set', 'Rosewood set in pure Chanderi with Mukeish work, paired with Santoon bottoms.', "Subtle elegance in Rosewood. Pure Chanderi with Mukeish work along the neckline, slits and hem, paired with Santoon bottoms.\n\nAvailable in your preferred shade and made to your measurements. Message us to order.", true],
    ];

    public function run(): void
    {
        $products = model(ProductModel::class);
        $added    = 0;

        foreach (self::PIECES as [$category, $name, $image, $summary, $description, $isPublished]) {
            if ($products->withDeleted()->where('name', $name)->countAllResults() > 0) {
                continue;
            }

            $products->insert([
                'category_id'     => $category === null ? null : $this->categoryId($category),
                'name'            => $name,
                'summary'         => $summary,
                'description'     => $description,
                'price'           => 0,
                'stock'           => 0,
                'image'           => $this->publishImage($image),
                'is_published'    => $isPublished ? 1 : 0,
                'is_enquiry_only' => 1,
            ]);

            $added++;
        }

        CLI::write("Added {$added} RUA pieces.", 'green');
    }

    private function categoryId(string $name): int
    {
        $categories = model(CategoryModel::class);
        $category   = $categories->where('name', $name)->first();

        if ($category !== null) {
            return $category->id;
        }

        return $categories->insert(['name' => $name, 'sort_order' => $categories->countAllResults() + 1], true);
    }

    /**
     * Writes the same sizes an admin upload produces.
     */
    private function publishImage(string $name): string
    {
        $file = $name . '.webp';

        foreach (ProductImages::SIZES as $folder => [$width, $height]) {
            $directory = FCPATH . 'uploads/products/' . $folder;

            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            service('image')
                ->withFile(APPPATH . 'Database/Seeds/rua/' . $name . '.jpg')
                ->fit($width, $height, 'center')
                ->convert(IMAGETYPE_WEBP)
                ->save($directory . '/' . $file, self::QUALITY);
        }

        return $file;
    }
}

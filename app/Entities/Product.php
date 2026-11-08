<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Product extends Entity
{
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts = [
        'id'              => 'int',
        'category_id'     => '?int',
        'price'           => 'int',
        'stock'           => 'int',
        'is_published'    => 'boolean',
        'is_enquiry_only' => 'boolean',
    ];

    /** @var list<ProductVariant> */
    private array $variants = [];

    public function isInStock(): bool
    {
        return $this->stock > 0;
    }

    /** Enquiry-only pieces are made to order, so they never count as sold out. */
    public function isSoldOut(): bool
    {
        return ! $this->is_enquiry_only && ! $this->isInStock();
    }

    public function canBeBought(): bool
    {
        return ! $this->is_enquiry_only && $this->isInStock();
    }

    /**
     * Filled in by ProductModel whenever a piece is read, in master list order.
     *
     * @param list<ProductVariant> $variants
     */
    public function setVariants(array $variants): static
    {
        $this->variants = $variants;

        return $this;
    }

    /**
     * Read as $product->variants, empty for a piece with no colours or sizes.
     *
     * @return list<ProductVariant>
     */
    public function getVariants(): array
    {
        return $this->variants;
    }

    /**
     * Read as $product->colour_list
     *
     * @return list<string>
     */
    public function getColourList(): array
    {
        return $this->optionList('colour');
    }

    /**
     * Read as $product->size_list
     *
     * @return list<string>
     */
    public function getSizeList(): array
    {
        return $this->optionList('size');
    }

    public function variantFor(string $colour, string $size): ?ProductVariant
    {
        foreach ($this->variants as $variant) {
            if ($variant->matches($colour, $size)) {
                return $variant;
            }
        }

        return null;
    }

    /**
     * Whether a shopper's choice is valid for this piece. Each is empty when the
     * piece has no list to choose from, and must be a real combination when it does.
     */
    public function offers(string $colour, string $size): bool
    {
        return $this->variants === [] ? $colour === '' && $size === '' : $this->variantFor($colour, $size) !== null;
    }

    /** What is left of one choice. A piece with no options has just its own stock. */
    public function stockOf(string $colour, string $size): int
    {
        if ($this->variants === []) {
            return $colour === '' && $size === '' ? $this->stock : 0;
        }

        return $this->variantFor($colour, $size)?->stock ?? 0;
    }

    /** What is left of a colour across its sizes, or a size across its colours. */
    public function unitsLeft(?string $colour = null, ?string $size = null): int
    {
        $left = 0;

        foreach ($this->variants as $variant) {
            if (($colour === null || $variant->colour === $colour) && ($size === null || $variant->size === $size)) {
                $left += $variant->stock;
            }
        }

        return $left;
    }

    /**
     * Read as $product->stock_map, for the product page: stock by colour, then size.
     *
     * @return array<string, array<string, int>>
     */
    public function getStockMap(): array
    {
        $map = [];

        foreach ($this->variants as $variant) {
            $map[(string) $variant->colour][(string) $variant->size] = $variant->stock;
        }

        return $map;
    }

    /** The swatch colour for one of this piece's colours. */
    public function swatchOf(string $colour): string
    {
        foreach ($this->variants as $variant) {
            if ($variant->colour === $colour) {
                return (string) $variant->colour_hex;
            }
        }

        return '';
    }

    /** Read as $product->image_url, null when no photo has been added. */
    public function getImageUrl(): ?string
    {
        return $this->uploadUrl('large');
    }

    /** Read as $product->thumb_url */
    public function getThumbUrl(): ?string
    {
        return $this->uploadUrl('thumb');
    }

    /** The address of one of the file names from ProductModel::photosOf(). */
    public function photoUrl(string $file, string $size = 'large'): string
    {
        return base_url('uploads/products/' . $size . '/' . $file);
    }

    /**
     * @return list<string> the names in use for colour or size, without repeats
     */
    private function optionList(string $option): array
    {
        $names = array_map(static fn (ProductVariant $variant): ?string => $variant->{$option}, $this->variants);

        return array_values(array_unique(array_filter($names, static fn (?string $name): bool => $name !== null)));
    }

    private function uploadUrl(string $size): ?string
    {
        $file = $this->attributes['image'] ?? null;

        return $file ? $this->photoUrl($file, $size) : null;
    }
}

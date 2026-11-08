<?php

namespace App\Models\Traits;

/**
 * Generates a unique URL slug from `name` at insert. It is never regenerated,
 * so renaming a record later does not break links people have saved.
 */
trait Sluggable
{
    protected function addSlug(array $data): array
    {
        if (empty($data['data']['slug']) && ! empty($data['data']['name'])) {
            $data['data']['slug'] = $this->uniqueSlug($data['data']['name']);
        }

        return $data;
    }

    private function uniqueSlug(string $name): string
    {
        $base = url_title($name, '-', true);
        $base = mb_substr($base === '' ? 'item' : $base, 0, 80);
        $slug = $base;

        // Uses a fresh builder so soft-deleted rows, which still hold their slug, are counted.
        for ($n = 2; $this->db->table($this->table)->where('slug', $slug)->countAllResults() > 0; $n++) {
            $slug = $base . '-' . $n;
        }

        return $slug;
    }
}

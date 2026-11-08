<?php

namespace App\Libraries;

/** Discovers the RUA brand film: 4K for larger screens and a lighter copy for phones. */
class FrameSequence
{
    private const SETS = [
        'desktop' => 'rua-scroll-frames-4k-webp/webp',
        'mobile'  => 'rua-scroll-frames-mobile/webp',
    ];

    public function __construct(private readonly string $root = FCPATH . 'assets/')
    {
    }

    /**
     * @return array<string, array{count: int, url: string, version: int, width: int, height: int}>
     */
    public function sets(): array
    {
        $sets = [];

        foreach (self::SETS as $name => $folder) {
            $set = $this->scan($folder);

            if ($set !== null) {
                $sets[$name] = $set;
            }
        }

        return $sets;
    }

    /**
     * @return array{count: int, url: string, version: int, width: int, height: int}|null
     */
    private function scan(string $folder): ?array
    {
        $path  = "{$this->root}{$folder}/";
        $first = $path . 'frame_0001.webp';

        if (! is_file($first) || ($size = getimagesize($first)) === false) {
            return null;
        }

        // Only expose a continuous sequence, so gaps never generate broken URLs.
        $count   = 0;
        $version = 0;

        while (is_file($frame = sprintf('%sframe_%04d.webp', $path, $count + 1))) {
            $version = max($version, filemtime($frame));
            $count++;
        }

        return [
            'count'   => $count,
            'url'     => base_url("assets/{$folder}"),
            'version' => $version,
            'width'   => $size[0],
            'height'  => $size[1],
        ];
    }
}

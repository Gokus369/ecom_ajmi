<?php

namespace Tests\Unit;

use App\Libraries\FrameSequence;
use CodeIgniter\Test\CIUnitTestCase;

final class FrameSequenceTest extends CIUnitTestCase
{
    private const DESKTOP = 'rua-scroll-frames-4k-webp/webp/';
    private const MOBILE  = 'rua-scroll-frames-mobile/webp/';

    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir() . '/rua-frames-' . uniqid() . '/';
        mkdir($this->root . self::DESKTOP, 0777, true);
        mkdir($this->root . self::MOBILE, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach ([self::DESKTOP, self::MOBILE] as $folder) {
            array_map('unlink', glob($this->root . $folder . '*') ?: []);
            rmdir($this->root . $folder);
            rmdir(dirname($this->root . $folder));
        }

        rmdir($this->root);
        parent::tearDown();
    }

    public function testNoFramesMeansNoSets(): void
    {
        $this->assertSame([], (new FrameSequence($this->root))->sets());
    }

    public function testBothSetsAreCountedAndMeasuredFromDisk(): void
    {
        $this->writeFrames(self::DESKTOP, 3, 160, 90);
        $this->writeFrames(self::MOBILE, 2, 80, 45);

        $sets = (new FrameSequence($this->root))->sets();

        $this->assertSame(['desktop', 'mobile'], array_keys($sets));
        $this->assertSame(3, $sets['desktop']['count']);
        $this->assertSame([160, 90], [$sets['desktop']['width'], $sets['desktop']['height']]);
        $this->assertStringEndsWith('assets/rua-scroll-frames-4k-webp/webp', $sets['desktop']['url']);
        $this->assertSame(2, $sets['mobile']['count']);
        $this->assertSame([80, 45], [$sets['mobile']['width'], $sets['mobile']['height']]);
        $this->assertStringEndsWith('assets/rua-scroll-frames-mobile/webp', $sets['mobile']['url']);
    }

    public function testAMissingSetIsLeftOut(): void
    {
        $this->writeFrames(self::MOBILE, 2, 80, 45);

        $this->assertSame(['mobile'], array_keys((new FrameSequence($this->root))->sets()));
    }

    public function testSequenceStopsBeforeAMissingFrame(): void
    {
        $this->writeFrames(self::DESKTOP, 3, 160, 90);
        unlink($this->root . self::DESKTOP . 'frame_0002.webp');

        $this->assertSame(1, (new FrameSequence($this->root))->sets()['desktop']['count']);
    }

    public function testReplacingALaterFrameUpdatesTheCacheVersion(): void
    {
        $this->writeFrames(self::DESKTOP, 3, 160, 90);
        $version = time() + 60;
        touch($this->root . self::DESKTOP . 'frame_0003.webp', $version);

        $this->assertSame($version, (new FrameSequence($this->root))->sets()['desktop']['version']);
    }

    private function writeFrames(string $folder, int $count, int $width, int $height): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $image = imagecreatetruecolor($width, $height);
            imagewebp($image, sprintf('%s%sframe_%04d.webp', $this->root, $folder, $i));
            imagedestroy($image);
        }
    }
}

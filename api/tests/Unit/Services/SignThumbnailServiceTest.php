<?php

namespace Tests\Unit\Services;

use App\Services\SignThumbnailService;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use PHPUnit\Framework\TestCase;

class SignThumbnailServiceTest extends TestCase
{
    public function test_it_scales_a_large_image_down_to_the_target_height_and_encodes_webp(): void
    {
        $service = new SignThumbnailService;
        $upload = $this->makePngUpload('wide-sign.png', 4096, 512);

        $thumbnail = $service->generate($upload);

        $this->assertNotNull($thumbnail);

        $image = ImageManager::usingDriver(Driver::class)->decode($thumbnail);

        $this->assertSame('image/webp', $image->origin()->mediaType());
        $this->assertSame(128, $image->height());
        $this->assertSame(1024, $image->width());
        $this->assertLessThan($upload->getSize(), strlen($thumbnail));
    }

    public function test_it_does_not_upscale_images_smaller_than_the_target_height(): void
    {
        $service = new SignThumbnailService;
        $upload = $this->makePngUpload('small-sign.png', 64, 32);

        $thumbnail = $service->generate($upload);

        $this->assertNotNull($thumbnail);

        $image = ImageManager::usingDriver(Driver::class)->decode($thumbnail);

        $this->assertSame(32, $image->height());
        $this->assertSame(64, $image->width());
    }

    public function test_it_skips_unsupported_mime_types(): void
    {
        $service = new SignThumbnailService;

        $path = tempnam(sys_get_temp_dir(), 'webm_');
        file_put_contents($path, 'not-a-real-video');

        $upload = new UploadedFile($path, 'sign.webm', 'video/webm', null, true);

        $this->assertNull($service->generate($upload));

        @unlink($path);
    }

    private function makePngUpload(string $name, int $width, int $height): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'png_');

        if ($path === false) {
            $this->fail('Unable to create a temporary PNG file.');
        }

        $image = imagecreatetruecolor($width, $height);
        $background = imagecolorallocate($image, 32, 64, 96);
        imagefill($image, 0, 0, $background);
        imagepng($image, $path);
        imagedestroy($image);

        return new UploadedFile($path, $name, 'image/png', null, true);
    }
}

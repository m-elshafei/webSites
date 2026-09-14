<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageComparisonTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    public function test_image_comparison_page_is_accessible(): void
    {
        $response = $this->get(route('image.comparison.index'));

        $response->assertStatus(200);
        $response->assertSee('مقارنة أداء وصيغ الصور');
        $response->assertSee('معالجة ومقارنة الصورة');
    }

    public function test_image_comparison_validates_required_image(): void
    {
        $response = $this->from(route('image.comparison.index'))->post(route('image.comparison.store'), []);

        $response->assertSessionHasErrors(['image']);
        $response->assertRedirect(route('image.comparison.index'));
    }

    public function test_image_comparison_processes_three_versions_successfully_via_web(): void
    {
        $file = UploadedFile::fake()->image('sample.jpg', 600, 400);

        $response = $this->post(route('image.comparison.store'), [
            'image' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertSee('الصورة الأصلية (Original)');
        $response->assertSee('مضغوطة بنفس الصيغة (Compressed)');
        $response->assertSee('محولة إلى صيغة WebP');
        $response->assertSee('نتائج المقارنة بين الحالات الثلاث');
    }

    public function test_image_comparison_returns_api_resource_for_json_requests(): void
    {
        $file = UploadedFile::fake()->image('test-photo.png', 400, 300);

        $response = $this->postJson('/api/image-comparison', [
            'image' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'key',
                    'title',
                    'subtitle',
                    'format',
                    'dimensions',
                    'path',
                    'url',
                    'size_kb',
                    'savings_percent',
                    'is_winner',
                ],
            ],
        ]);

        $data = $response->json('data');
        $this->assertCount(3, $data);
        $this->assertSame('original', $data[0]['key']);
        $this->assertSame('compressed', $data[1]['key']);
        $this->assertSame('webp', $data[2]['key']);

        Storage::disk('public')->assertExists($data[0]['path']);
        Storage::disk('public')->assertExists($data[1]['path']);
        Storage::disk('public')->assertExists($data[2]['path']);
    }
}

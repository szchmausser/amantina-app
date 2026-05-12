<?php

namespace Tests\Feature\Settings;

use App\Models\Institution;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InstitutionLogoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_logo_can_be_uploaded()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $institution = Institution::create(['name' => 'Test School']);

        $file = UploadedFile::fake()->image('logo.png', 200, 200);

        $response = $this->actingAs($user)->post(route('institution.logo.update'), [
            'logo' => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('media', [
            'model_id' => $institution->id,
            'model_type' => Institution::class,
            'collection_name' => 'logo',
        ]);

        $institution->refresh();
        $this->assertNotNull($institution->logo_url);
    }

    public function test_upload_replaces_existing_logo()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $institution = Institution::create(['name' => 'Test School']);

        // Upload first logo
        $file1 = UploadedFile::fake()->image('logo1.png', 200, 200);
        $this->actingAs($user)->post(route('institution.logo.update'), ['logo' => $file1]);

        $institution->refresh();
        $firstMediaId = $institution->getFirstMedia('logo')->id;

        // Upload second logo
        $file2 = UploadedFile::fake()->image('logo2.png', 200, 200);
        $this->actingAs($user)->post(route('institution.logo.update'), ['logo' => $file2]);

        $this->assertDatabaseMissing('media', ['id' => $firstMediaId]);
        $this->assertDatabaseHas('media', [
            'model_id' => $institution->id,
            'model_type' => Institution::class,
            'collection_name' => 'logo',
        ]);

        $institution->refresh();
        $this->assertCount(1, $institution->getMedia('logo'));
        $this->assertNotEquals($firstMediaId, $institution->getFirstMedia('logo')->id);
    }

    public function test_logo_requires_valid_image_type()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        Institution::create(['name' => 'Test School']);

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)->post(route('institution.logo.update'), [
            'logo' => $file,
        ]);

        $response->assertSessionHasErrors(['logo']);
    }

    public function test_logo_cannot_exceed_max_size()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        Institution::create(['name' => 'Test School']);

        // 3MB file (exceeds 2MB limit)
        $file = UploadedFile::fake()->image('logo.png')->size(3072);

        $response = $this->actingAs($user)->post(route('institution.logo.update'), [
            'logo' => $file,
        ]);

        $response->assertSessionHasErrors(['logo']);
    }

    public function test_logo_can_be_removed()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $institution = Institution::create(['name' => 'Test School']);

        // Upload a logo first
        $file = UploadedFile::fake()->image('logo.png', 200, 200);
        $this->actingAs($user)->post(route('institution.logo.update'), ['logo' => $file]);

        $this->assertDatabaseHas('media', [
            'model_id' => $institution->id,
            'model_type' => Institution::class,
            'collection_name' => 'logo',
        ]);

        // Remove it
        $response = $this->actingAs($user)->delete(route('institution.logo.remove'));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseMissing('media', [
            'model_id' => $institution->id,
            'model_type' => Institution::class,
            'collection_name' => 'logo',
        ]);

        $this->assertNull($institution->fresh()->logo_url);
    }

    public function test_remove_when_no_logo_succeeds()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        Institution::create(['name' => 'Test School']);

        $response = $this->actingAs($user)->delete(route('institution.logo.remove'));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
    }

    public function test_favicon_serves_logo_conversion()
    {
        $institution = Institution::create(['name' => 'Test School']);
        $file = UploadedFile::fake()->image('logo.png', 200, 200);
        $institution->addMedia($file)->toMediaCollection('logo');
        $institution->refresh();

        // Verify media exists before hitting favicon route
        $this->assertTrue($institution->hasMedia('logo'));
        $this->assertNotNull($institution->favicon_url);

        $media = $institution->getFirstMedia('logo');
        $this->assertFileExists($media->getPath());

        $response = $this->get('/favicon');

        $response->assertStatus(200);
        $this->assertStringStartsWith('image/', $response->headers->get('Content-Type'));
        $this->assertNotNull($response->headers->get('Cache-Control'));
    }

    public function test_favicon_returns_fallback_when_no_logo()
    {
        Institution::create(['name' => 'Test School']);

        $response = $this->get('/favicon');

        // Should redirect to default favicon.ico when no logo
        $response->assertStatus(302);
    }

    public function test_logo_upload_requires_authentication()
    {
        $file = UploadedFile::fake()->image('logo.png');

        $this->post(route('institution.logo.update'), ['logo' => $file]);

        $this->assertDatabaseCount('media', 0);
    }

    public function test_logo_remove_requires_authentication()
    {
        $institution = Institution::create(['name' => 'Test School']);
        $file = UploadedFile::fake()->image('logo.png');
        $institution->addMedia($file)->toMediaCollection('logo');

        $this->delete(route('institution.logo.remove'));

        // Verify the logo was NOT removed (unauthenticated request)
        $this->assertDatabaseHas('media', [
            'model_id' => $institution->id,
            'model_type' => Institution::class,
            'collection_name' => 'logo',
        ]);
    }

    public function test_institution_data_is_shared_globally()
    {
        $institution = Institution::create([
            'name' => 'Test School',
        ]);

        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('institution.name', 'Test School')
            ->has('institution.logo_url')
            ->has('institution.favicon_url')
        );
    }
}

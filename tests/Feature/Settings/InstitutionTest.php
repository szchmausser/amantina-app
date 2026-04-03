<?php

namespace Tests\Feature\Settings;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_institution_settings_page_can_be_rendered()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('institution.edit'));

        $response->assertOk();
    }

    public function test_institution_settings_can_be_updated()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('institution.update'), [
            'name' => 'New Institution Name',
            'address' => 'New Address',
            'email' => 'new@institution.com',
            'phone' => '04128888888',
            'code' => 'NEW-001',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $institution = Institution::first();
        $this->assertEquals('New Institution Name', $institution->name);
        $this->assertEquals('New Address', $institution->address);
        $this->assertEquals('new@institution.com', $institution->email);
        $this->assertEquals('04128888888', $institution->phone);
        $this->assertEquals('NEW-001', $institution->code);
    }

    public function test_institution_settings_requires_name()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('institution.update'), [
            'name' => '',
        ]);

        $response->assertSessionHasErrors(['name']);
    }
}

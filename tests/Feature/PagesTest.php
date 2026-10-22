<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_customers_page_renders_the_livewire_table(): void
    {
        Customer::query()->insert([
            'customer_code' => 'C001',
            'name' => 'Acme Stores',
            'region' => 'Nairobi',
            'location' => 'Industrial Area',
            'customer_warehouse' => 'UNT02',
            'pricelist_code' => 'PL1',
            'credit_limit' => 1000,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs(User::factory()->create())
            ->get('/customers')
            ->assertOk()
            ->assertSee('Acme Stores')
            ->assertSee('Posted');
    }

    public function test_users_page_renders_for_authenticated_users(): void
    {
        $this->actingAs(User::factory()->create(['name' => 'Jane Admin']))
            ->get('/users')
            ->assertOk()
            ->assertSee('Jane Admin')
            ->assertSee('Create New User');
    }

    public function test_profile_page_renders(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/user/profile')
            ->assertOk()
            ->assertSee('Profile Information');
    }

    public function test_guests_are_redirected_from_app_pages(): void
    {
        $this->get('/customers')->assertRedirect('/login');
    }
}

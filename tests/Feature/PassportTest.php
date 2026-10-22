<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Tests\TestCase;

class PassportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $keyPath = storage_path('framework/testing/oauth');
        File::ensureDirectoryExists($keyPath);
        Passport::loadKeysFrom($keyPath);
        Passport::$validateKeyPermissions = false;

        if (! File::exists($keyPath.'/oauth-private.key')) {
            Artisan::call('passport:keys', ['--force' => true]);
        }
    }

    public function test_api_login_issues_a_personal_access_token(): void
    {
        Artisan::call('passport:client', ['--personal' => true, '--name' => 'Testing', '--no-interaction' => true]);

        $user = User::factory()->create(['password' => 'secret-password']);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ]);

        $response->assertOk()->assertJsonStructure(['user' => ['id', 'email'], 'access_token']);
        $this->assertNotEmpty($response->json('access_token'));
    }

    public function test_client_credentials_token_passes_the_client_middleware(): void
    {
        Route::middleware('client')->get('/api/_client-probe', fn () => ['ok' => true]);

        $client = app(ClientRepository::class)->createClientCredentialsGrantClient('SFA');

        $this->getJson('/api/_client-probe')->assertUnauthorized();

        $token = $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
        ])->assertOk()->json('access_token');

        $this->withToken($token)->getJson('/api/_client-probe')->assertOk()->assertJson(['ok' => true]);
    }
}

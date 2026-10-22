<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Passport;

/**
 * Converts an oauth_clients table created by Passport 10 to the Passport 13 layout.
 * Fresh installs already get the new layout from Passport's own migration, so this
 * only runs when the legacy columns are present.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('oauth_clients') || ! Schema::hasColumn('oauth_clients', 'user_id')) {
            return;
        }

        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->nullableMorphs('owner', after: 'user_id');

            $table->after('provider', function (Blueprint $table) {
                $table->text('redirect_uris')->nullable();
                $table->text('grant_types')->nullable();
            });
        });

        foreach (Passport::client()->cursor() as $client) {
            Model::withoutTimestamps(fn () => $client->forceFill([
                'owner_id' => $client->user_id,
                'owner_type' => $client->user_id
                    ? config('auth.providers.'.($client->provider ?: config('auth.guards.api.provider')).'.model')
                    : null,
                'redirect_uris' => $client->redirect_uris,
                'grant_types' => $client->grant_types,
            ])->save());
        }

        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->dropColumn(['user_id', 'redirect', 'personal_access_client', 'password_client']);

            $table->text('redirect_uris')->nullable(false)->change();
            $table->text('grant_types')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        // The legacy columns cannot be reconstructed losslessly; restore from backup instead.
    }
};

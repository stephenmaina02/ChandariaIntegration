<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDiscountSyncColumnsToCustomersTable extends Migration
{
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'discount')) {
                $table->decimal('discount', 8, 2)->default(0);
            }

            // 1 = SFA holds the current discount, 0 = pending push
            if (!Schema::hasColumn('customers', 'discount_status')) {
                $table->tinyInteger('discount_status')->default(1)->index();
            }

            if (!Schema::hasColumn('customers', 'discount_changed_at')) {
                $table->timestamp('discount_changed_at')->nullable();
            }

            if (!Schema::hasColumn('customers', 'discount_synced_at')) {
                $table->timestamp('discount_synced_at')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['discount_status', 'discount_changed_at', 'discount_synced_at']);
        });
    }
}

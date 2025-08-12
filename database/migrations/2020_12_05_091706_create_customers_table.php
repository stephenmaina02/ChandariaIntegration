<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('region');
            $table->string('location');
            $table->string('account')->nullable();
            $table->string('category')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('customer_code');
            $table->string('customer_warehouse');
            $table->string('pricelist_code');
            $table->double('credit_limit');
            $table->string('email')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('kra_pin')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('postal_address')->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customers');
    }
}

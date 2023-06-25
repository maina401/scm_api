<?php

namespace Leaf\Helpers;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class create_all_tables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Capsule::schema()->defaultStringLength(191);

        Capsule::schema()->create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 20)->unique();
            $table->string('password');
            $table->string('role', 20)->default('user');
            $table->string('status', 20)->default('active');
            $table->string('merchant_secret')->nullable();
            $table->string('remember_token')->nullable();
            $table->string('avatar')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Capsule::schema()->create('password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        //jwt table
        Capsule::schema()->create('jwt', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('user_id');
            $table->string('token');
            $table->string('ip_address');
            $table->string('user_agent');
            $table->string('payload');
            $table->string('last_activity');
            $table->timestamps();
            $table->softDeletes();
        });

        //items table
        Capsule::schema()->create('items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('price');
            $table->integer('quantity')->default(0);
            $table->string('image')->nullable();
            $table->string('status', 20)->default('active');
            $table->json('meta')->nullable();
            $table->uuid('created_by');
            $table->timestamps();
            $table->softDeletes();
        });

        //events table
        Capsule::schema()->create('events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('description')->nullable();
            $table->string('status', 20)->default('active');
            $table->json('meta')->nullable();
            $table->uuid('created_by');
            $table->timestamps();
            $table->softDeletes();
        });

        //item events table
        Capsule::schema()->create('item_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('item_id');
            $table->string('event_id');
            $table->string('value');
            $table->json('meta')->nullable();
            $table->uuid('created_by');
            $table->timestamps();
            $table->softDeletes();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //drop jwt table
        Capsule::schema()->dropIfExists('jwt');
        Capsule::schema()->dropIfExists('password_resets');
        Capsule::schema()->dropIfExists('users');
        Capsule::schema()->dropIfExists('items');
        Capsule::schema()->dropIfExists('events');
        Capsule::schema()->dropIfExists('item_events');
    }


}

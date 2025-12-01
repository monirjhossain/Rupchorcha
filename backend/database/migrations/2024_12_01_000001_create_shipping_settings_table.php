<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shipping_settings', function (Blueprint $table) {
            $table->id();
            $table->string('method_code')->unique();
            $table->string('method_name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('delivery_time_min')->default(1);
            $table->integer('delivery_time_max')->default(3);
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Insert default shipping methods
        DB::table('shipping_settings')->insert([
            [
                'method_code' => 'inside_dhaka',
                'method_name' => 'Inside Dhaka',
                'description' => 'Delivery inside Dhaka city',
                'price' => 70,
                'delivery_time_min' => 1,
                'delivery_time_max' => 2,
                'active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'method_code' => 'outside_dhaka',
                'method_name' => 'Outside Dhaka',
                'description' => 'Delivery outside Dhaka',
                'price' => 130,
                'delivery_time_min' => 3,
                'delivery_time_max' => 5,
                'active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Create general shipping settings
        Schema::create('general_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Insert free shipping threshold
        DB::table('general_settings')->insert([
            'key' => 'free_shipping_threshold',
            'value' => '3000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_settings');
        Schema::dropIfExists('general_settings');
    }
};

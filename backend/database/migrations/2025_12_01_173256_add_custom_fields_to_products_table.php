<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomFieldsToProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('name')->nullable()->after('sku');
            $table->string('slug')->nullable()->unique()->after('name');
            $table->text('description')->nullable()->after('slug');
            $table->text('short_description')->nullable()->after('description');
            $table->decimal('price', 10, 2)->nullable()->after('short_description');
            $table->decimal('special_price', 10, 2)->nullable()->after('price');
            $table->integer('quantity')->default(0)->after('special_price');
            $table->decimal('weight', 10, 2)->nullable()->after('quantity');
            $table->boolean('status')->default(1)->after('weight');
            $table->boolean('featured')->default(0)->after('status');
            $table->boolean('new')->default(0)->after('featured');
            $table->string('meta_title')->nullable()->after('new');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->text('meta_keywords')->nullable()->after('meta_description');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'name', 'slug', 'description', 'short_description', 
                'price', 'special_price', 'quantity', 'weight',
                'status', 'featured', 'new',
                'meta_title', 'meta_description', 'meta_keywords'
            ]);
        });
    }
}

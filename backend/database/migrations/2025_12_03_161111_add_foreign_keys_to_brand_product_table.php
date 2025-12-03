<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToBrandProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('brand_product', function (Blueprint $table) {
            $table->unsignedInteger('product_id')->after('id');
            $table->unsignedBigInteger('brand_id')->after('product_id');
            
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('cascade');
            
            $table->unique(['product_id', 'brand_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('brand_product', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['brand_id']);
            $table->dropUnique(['product_id', 'brand_id']);
            $table->dropColumn(['product_id', 'brand_id']);
        });
    }
}

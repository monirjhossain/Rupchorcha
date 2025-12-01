<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PopulateProductDataFromFlat extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Check if product_flat table exists
        if (!Schema::hasTable('product_flat')) {
            return;
        }

        // Copy data from product_flat to products table
        $flatProducts = DB::table('product_flat')
            ->where('locale', 'en')
            ->get();

        foreach ($flatProducts as $flat) {
            DB::table('products')
                ->where('id', $flat->product_id)
                ->update([
                    'name' => $flat->name ?? null,
                    'slug' => $flat->url_key ?? null,
                    'description' => $flat->description ?? null,
                    'short_description' => $flat->short_description ?? null,
                    'price' => $flat->price ?? 0,
                    'special_price' => $flat->special_price ?? null,
                    'weight' => $flat->weight ?? null,
                    'status' => $flat->status ?? 1,
                    'featured' => $flat->featured ?? 0,
                    'new' => $flat->new ?? 0,
                    'meta_title' => $flat->meta_title ?? null,
                    'meta_description' => $flat->meta_description ?? null,
                    'meta_keywords' => $flat->meta_keywords ?? null,
                ]);
        }

        // Get inventory quantities from product_inventories
        if (Schema::hasTable('product_inventories')) {
            $inventories = DB::table('product_inventories')
                ->select('product_id', DB::raw('SUM(qty) as total_qty'))
                ->groupBy('product_id')
                ->get();

            foreach ($inventories as $inventory) {
                DB::table('products')
                    ->where('id', $inventory->product_id)
                    ->update(['quantity' => $inventory->total_qty]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Optional: Clear the copied data
        DB::table('products')->update([
            'name' => null,
            'slug' => null,
            'description' => null,
            'short_description' => null,
            'price' => null,
            'special_price' => null,
            'quantity' => 0,
            'weight' => null,
            'status' => 1,
            'featured' => 0,
            'new' => 0,
            'meta_title' => null,
            'meta_description' => null,
            'meta_keywords' => null,
        ]);
    }
}

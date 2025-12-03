<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddSampleSliders extends Migration
{
    public function up()
    {
        // Check if channels exist first
        $channel = DB::table('channels')->first();
        
        if ($channel) {
            // Insert sample sliders
            DB::table('sliders')->insert([
                [
                    'title' => 'Summer Sale',
                    'path' => 'sliders/slider1.jpg',
                    'content' => 'Up to 50% Off',
                    'channel_id' => $channel->id,
                    'locale' => 'en',
                    'sort_order' => 1,
                    'slider_path' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title' => 'New Collection',
                    'path' => 'sliders/slider2.jpg',
                    'content' => 'Latest Products',
                    'channel_id' => $channel->id,
                    'locale' => 'en',
                    'sort_order' => 2,
                    'slider_path' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title' => 'Special Offer',
                    'path' => 'sliders/slider3.jpg',
                    'content' => 'Buy 1 Get 1 Free',
                    'channel_id' => $channel->id,
                    'locale' => 'en',
                    'sort_order' => 3,
                    'slider_path' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    public function down()
    {
        DB::table('sliders')->truncate();
    }
}

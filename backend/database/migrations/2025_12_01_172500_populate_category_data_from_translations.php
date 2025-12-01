<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class PopulateCategoryDataFromTranslations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Copy data from category_translations (English locale) to categories table
        $translations = DB::table('category_translations')
            ->where('locale', 'en')
            ->get();

        foreach ($translations as $translation) {
            DB::table('categories')
                ->where('id', $translation->category_id)
                ->update([
                    'name' => $translation->name,
                    'slug' => $translation->slug,
                    'description' => $translation->description,
                    'meta_title' => $translation->meta_title,
                    'meta_description' => $translation->meta_description,
                    'meta_keywords' => $translation->meta_keywords,
                ]);
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
        DB::table('categories')->update([
            'name' => null,
            'slug' => null,
            'description' => null,
            'meta_title' => null,
            'meta_description' => null,
            'meta_keywords' => null,
        ]);
    }
}

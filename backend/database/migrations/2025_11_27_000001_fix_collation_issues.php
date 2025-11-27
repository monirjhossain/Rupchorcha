<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class FixCollationIssues extends Migration
{
    public function up()
    {
        $dbName = env('DB_DATABASE', 'rupchorcha_backend');
        
        // Fix categories table
        DB::unprepared('ALTER TABLE categories CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        
        // Fix category_translations table
        DB::unprepared('ALTER TABLE category_translations CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        
        // Fix products table
        DB::unprepared('ALTER TABLE products CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        
        // Fix product_flat table
        DB::unprepared('ALTER TABLE product_flat CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        
        // Fix attributes table
        DB::unprepared('ALTER TABLE attributes CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        
        // Fix database
        DB::unprepared("ALTER DATABASE {$dbName} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    public function down()
    {
        // No rollback needed
    }
}

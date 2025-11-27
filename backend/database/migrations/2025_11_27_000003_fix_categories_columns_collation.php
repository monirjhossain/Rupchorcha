<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class FixCategoriesColumnsCollation extends Migration
{
    public function up()
    {
        // Fix specific columns in categories table that might have different collations
        $columns = [
            '_lft',
            '_rgt',
            'parent_id',
            'position',
            'status',
            'display_mode',
            'created_at',
            'updated_at'
        ];
        
        // Drop indexes that might prevent alteration
        try {
            DB::statement('ALTER TABLE categories DROP INDEX IF EXISTS categories__lft__rgt_parent_id_index');
        } catch (\Exception $e) {
            // Index might not exist
        }
        
        // Recreate the entire categories table structure with proper collation
        DB::statement('ALTER TABLE categories 
            MODIFY COLUMN `_lft` int(10) unsigned NOT NULL,
            MODIFY COLUMN `_rgt` int(10) unsigned NOT NULL,
            MODIFY COLUMN `parent_id` int(10) unsigned DEFAULT NULL,
            MODIFY COLUMN `position` int(11) NOT NULL DEFAULT 0,
            MODIFY COLUMN `status` tinyint(1) NOT NULL DEFAULT 0,
            MODIFY COLUMN `display_mode` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        
        // Recreate index
        try {
            DB::statement('CREATE INDEX categories__lft__rgt_parent_id_index ON categories (_lft, _rgt, parent_id)');
        } catch (\Exception $e) {
            // Index might already exist
        }
    }

    public function down()
    {
        // No rollback
    }
}

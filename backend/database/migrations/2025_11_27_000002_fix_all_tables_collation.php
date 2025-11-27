<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class FixAllTablesCollation extends Migration
{
    public function up()
    {
        $dbName = env('DB_DATABASE', 'rupchorcha_backend');
        
        // Get all tables
        $tables = DB::select('SHOW TABLES');
        $tableKey = 'Tables_in_' . $dbName;
        
        foreach ($tables as $table) {
            $tableName = $table->$tableKey;
            
            try {
                DB::unprepared("ALTER TABLE `{$tableName}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                echo "Fixed: {$tableName}\n";
            } catch (\Exception $e) {
                echo "Error fixing {$tableName}: " . $e->getMessage() . "\n";
            }
        }
        
        // Fix database default collation
        try {
            DB::unprepared("ALTER DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "Fixed database: {$dbName}\n";
        } catch (\Exception $e) {
            echo "Error fixing database: " . $e->getMessage() . "\n";
        }
    }

    public function down()
    {
        // No rollback
    }
}

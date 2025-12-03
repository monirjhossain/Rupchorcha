<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdminLogoToGeneralSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->string('site_name')->nullable()->after('value');
            $table->string('contact_email')->nullable()->after('site_name');
            $table->string('contact_phone')->nullable()->after('contact_email');
            $table->string('admin_logo')->nullable()->after('contact_phone');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn(['site_name', 'contact_email', 'contact_phone', 'admin_logo']);
        });
    }
}

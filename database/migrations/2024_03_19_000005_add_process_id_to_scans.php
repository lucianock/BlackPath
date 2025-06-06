<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->string('process_id')->nullable()->after('wordlist');
        });
    }

    public function down()
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->dropColumn('process_id');
        });
    }
}; 
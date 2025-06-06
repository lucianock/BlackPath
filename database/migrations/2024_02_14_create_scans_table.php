<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('scans', function (Blueprint $table) {
            $table->id();
            $table->string('domain');
            $table->enum('status', ['queued', 'initializing', 'running', 'completed', 'failed', 'cancelled'])->default('queued');
            $table->integer('progress')->default(0);
            $table->string('wordlist')->default('common');
            $table->string('status_message')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('scan_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_id')->constrained()->onDelete('cascade');
            $table->string('tool');
            $table->text('raw_output');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('scan_results');
        Schema::dropIfExists('scans');
    }
}; 
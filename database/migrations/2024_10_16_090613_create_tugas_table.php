<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tugas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('jurnal_id')->nullable(); 
            $table->date('deadline');
            $table->string('title');
            $table->string('description');
            $table->string('image')->nullable();
            $table->enum('status',['selesai',  'sedang dikerjakan'])->default('sedang dikerjakan');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('jurnal_id')->references('id')->on('jurnals')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tugas', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['jurnal_id']);
        });
        Schema::dropIfExists('tugas');
    }
};

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
        Schema::table('users', function (Blueprint $table) { 
            $table->string('job_status')->after('email_verified_at');
            $table->string('location')->after('email_verified_at');
            $table->string('bio')->after('email_verified_at');
            $table->string('phone_number')->unique()->after('email_verified_at');
            $table->date('birthdate')->after('email_verified_at');
            $table->string('username')->unique()->after('email_verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};

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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code', 50)->unique();
            $table->string('full_name', 150)->index();
            $table->string('national_id', 50)->nullable()->unique();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 30)->nullable();
            $table->string('marital_status', 30)->nullable();
            $table->string('personal_email', 254)->nullable();
            $table->string('official_email', 254)->nullable()->unique();
            $table->string('contact_number', 30)->nullable();
            $table->string('alternate_contact_number', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->foreignId('job_title_id')->constrained()->restrictOnDelete();
            $table->foreignId('reporting_manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('employment_type', 30)->index();
            $table->string('employment_status', 30)->index();
            $table->date('joining_date');
            $table->date('confirmation_date')->nullable();
            $table->string('work_location', 150)->nullable();
            $table->string('emergency_contact_name', 150)->nullable();
            $table->string('emergency_contact_relationship', 100)->nullable();
            $table->string('emergency_contact_number', 30)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};

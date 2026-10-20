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
        Schema::create('contract_expiry_notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('employee_contract_id');
            $table->date('contract_end_date');
            $table->timestamp('database_notified_at')->nullable();
            $table->timestamp('mail_notified_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id', 'contract_expiry_user_fk')
                ->references('id')->on('users')->restrictOnDelete();
            $table->foreign('employee_contract_id', 'contract_expiry_contract_fk')
                ->references('id')->on('employee_contracts')->restrictOnDelete();
            $table->unique(
                ['user_id', 'employee_contract_id', 'contract_end_date'],
                'contract_expiry_delivery_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_expiry_notification_deliveries');
    }
};

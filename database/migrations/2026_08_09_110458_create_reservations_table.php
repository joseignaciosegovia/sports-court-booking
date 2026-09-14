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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->foreignId('court_id')->constrained('courts');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('information', 255);
            $table->string('payment_id')->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'canceled', 'refunded'])
                  ->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->string('stripe_session_id')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->enum('canceled_by', ['client', 'manager', 'system'])->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->string('stripe_refund_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['canceled_at', 'canceled_by', 'cancellation_reason', 'refunded_at', 'stripe_refund_id']);
        });
    }
};

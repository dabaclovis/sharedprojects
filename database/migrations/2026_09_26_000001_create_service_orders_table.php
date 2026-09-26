<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->string('service', 40);
            $table->string('name', 100);
            $table->string('email');
            $table->string('website', 2048);
            $table->text('brief');
            $table->string('status', 30)->default('new');
            $table->unsignedInteger('amount_cents')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('payment_status', 20)->default('unpaid');
            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('internal_notes')->nullable();
            $table->text('deliverable')->nullable();
            $table->json('audit_result')->nullable();
            $table->json('history')->nullable();
            $table->string('sponsor_name', 100)->nullable();
            $table->string('sponsor_title', 120)->nullable();
            $table->string('sponsor_description', 400)->nullable();
            $table->string('sponsor_url', 2048)->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->index(['service', 'status', 'created_at']);
            $table->index(['service', 'payment_status', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_orders');
    }
};

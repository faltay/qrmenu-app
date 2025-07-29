<?php
// database/migrations/xxxx_create_invoices_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            
            // Invoice Identity
            $table->string('invoice_number')->unique();     // QR202412001, QR202412002
            $table->string('invoice_series', 10)->default('QR'); // Invoice series
            
            // Relations
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_subscription_id')->nullable()->constrained()->onDelete('set null');
            
            // Gateway Information
            $table->string('gateway');                       // 'stripe', 'iyzico', 'manual'
            $table->string('gateway_invoice_id')->nullable(); // External invoice ID
            $table->string('gateway_payment_intent_id')->nullable(); // Payment intent ID
            
            // Financial Details
            $table->decimal('subtotal', 10, 2);            // Amount before tax
            $table->decimal('tax_rate', 5, 4)->default(0); // Tax rate (0.18 for 18%)
            $table->decimal('tax_amount', 10, 2)->default(0); // Calculated tax
            $table->decimal('discount_amount', 10, 2)->default(0); // Discounts
            $table->decimal('total_amount', 10, 2);        // Final amount
            $table->string('currency', 3);                 // ISO currency code
            
            // Dates
            $table->timestamp('invoice_date');
            $table->timestamp('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('payment_attempted_at')->nullable();
            $table->integer('payment_attempts')->default(0);
            
            // Status
            $table->enum('status', [
                'draft',        // Not sent yet
                'sent',         // Sent to customer
                'viewed',       // Customer viewed
                'paid',         // Successfully paid
                'partially_paid', // Partial payment
                'overdue',      // Past due date
                'failed',       // Payment failed
                'cancelled',    // Cancelled
                'refunded'      // Refunded
            ])->default('draft');
            
            // Customer Information (Snapshot at invoice time)
            $table->json('customer_data');                 // Customer info snapshot
            $table->json('billing_address');               // Billing address snapshot
            
            // Company Information (Snapshot)
            $table->json('company_data');                  // Company info snapshot
            
            // Invoice Content
            $table->text('notes')->nullable();             // Invoice notes
            $table->json('payment_terms')->nullable();     // Payment terms & conditions
            $table->string('reference')->nullable();       // Customer reference
            
            // File Management
            $table->string('pdf_path')->nullable();        // Generated PDF path
            $table->timestamp('pdf_generated_at')->nullable();
            $table->string('pdf_hash')->nullable();        // PDF integrity check
            
            // Communication
            $table->timestamp('last_reminder_sent_at')->nullable();
            $table->integer('reminder_count')->default(0);
            $table->json('email_log')->nullable();         // Email sending log
            
            // Metadata
            $table->json('metadata')->nullable();          // Additional data
            $table->string('locale', 5)->default('en');    // Invoice language
            
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['gateway', 'gateway_invoice_id']);
            $table->index(['status', 'due_date']);
            $table->index(['invoice_date', 'status']);
            $table->index(['currency', 'total_amount']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
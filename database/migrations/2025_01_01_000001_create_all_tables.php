<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cache', function (Blueprint $t) { $t->string('key')->primary(); $t->mediumText('value'); $t->integer('expiration'); });
        Schema::create('cache_locks', function (Blueprint $t) { $t->string('key')->primary(); $t->string('owner'); $t->integer('expiration'); });
        Schema::create('jobs', function (Blueprint $t) { $t->id(); $t->string('queue')->index(); $t->longText('payload'); $t->unsignedTinyInteger('attempts'); $t->unsignedInteger('reserved_at')->nullable(); $t->unsignedInteger('available_at'); $t->unsignedInteger('created_at'); });
        Schema::create('job_batches', function (Blueprint $t) { $t->string('id')->primary(); $t->string('name'); $t->integer('total_jobs'); $t->integer('pending_jobs'); $t->integer('failed_jobs'); $t->longText('failed_job_ids'); $t->mediumText('options')->nullable(); $t->integer('cancelled_at')->nullable(); $t->integer('created_at'); $t->integer('finished_at')->nullable(); });
        Schema::create('failed_jobs', function (Blueprint $t) { $t->id(); $t->string('uuid')->unique(); $t->text('connection'); $t->text('queue'); $t->longText('payload'); $t->longText('exception'); $t->timestamp('failed_at')->useCurrent(); });

        Schema::table('users', function (Blueprint $t) { $t->string('first_name')->nullable()->after('name'); $t->string('last_name')->nullable()->after('first_name'); $t->string('phone')->nullable()->after('email'); $t->boolean('is_active')->default(true)->after('phone'); $t->softDeletes(); });

        Schema::create('magazines', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('short_name', 50)->nullable(); $t->string('slug')->unique();
            $t->string('type')->default('publication'); $t->text('description')->nullable(); $t->string('issn')->nullable();
            $t->string('publisher')->nullable(); $t->string('frequency')->default('monthly'); $t->string('cover_image')->nullable();
            $t->boolean('is_active')->default(true); $t->integer('sort_order')->default(0); $t->json('metadata')->nullable();
            $t->timestamps(); $t->softDeletes();
        });

        Schema::create('clients', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('client_number')->unique(); $t->string('external_code')->nullable()->index();
            $t->enum('type', ['individual', 'company'])->default('individual');
            $t->string('company_name')->nullable(); $t->string('siret')->nullable(); $t->string('vat_number')->nullable();
            $t->string('company_email')->nullable();
            $t->enum('civility', ['M', 'Mme', 'Dr', 'Pr'])->nullable();
            $t->string('first_name'); $t->string('last_name'); $t->string('email');
            $t->string('phone')->nullable(); $t->string('mobile')->nullable();
            $t->string('address_name')->nullable();
            $t->string('address_line1'); $t->string('address_line2')->nullable(); $t->string('address_line3')->nullable();
            $t->string('postal_code', 10); $t->string('city'); $t->string('cedex')->nullable(); $t->string('country', 2)->default('FR');
            $t->string('delivery_address_name')->nullable();
            $t->string('delivery_address_line1')->nullable(); $t->string('delivery_address_line2')->nullable();
            $t->string('delivery_address_line3')->nullable(); $t->string('delivery_postal_code', 10)->nullable();
            $t->string('delivery_city')->nullable(); $t->string('delivery_cedex')->nullable();
            $t->string('delivery_country', 2)->nullable(); $t->boolean('use_delivery_address')->default(false);
            $t->text('notes')->nullable(); $t->json('custom_fields')->nullable();
            $t->boolean('is_active')->default(true); $t->string('status', 20)->default('active');
            $t->string('deactivation_reason')->nullable(); $t->timestamp('deactivated_at')->nullable();
            $t->timestamp('archived_at')->nullable(); $t->string('archived_reason')->nullable();
            $t->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            $t->boolean('is_payer')->default(false);
            $t->foreignId('payer_client_id')->nullable()->constrained('clients')->nullOnDelete();
            $t->timestamps(); $t->softDeletes();
            $t->index(['last_name', 'first_name']); $t->index('email'); $t->index('postal_code'); $t->index('status');
        });

        Schema::table('users', function (Blueprint $t) { $t->foreignId('client_id')->nullable()->after('is_active')->constrained()->nullOnDelete(); });

        Schema::create('payers', function (Blueprint $t) {
            $t->id(); $t->foreignId('client_id')->constrained()->cascadeOnDelete();
            $t->foreignId('payer_client_id')->constrained('clients')->cascadeOnDelete();
            $t->text('notes')->nullable(); $t->boolean('is_active')->default(true);
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps(); $t->unique(['client_id', 'payer_client_id']);
        });

        Schema::create('subscription_plans', function (Blueprint $t) {
            $t->id(); $t->foreignId('magazine_id')->constrained()->cascadeOnDelete();
            $t->string('name'); $t->string('slug'); $t->text('description')->nullable();
            $t->enum('support_type', ['paper', 'digital', 'combined']); $t->enum('mode', ['duration', 'issues']);
            $t->integer('duration_months')->nullable(); $t->integer('issues_count')->nullable();
            $t->decimal('price', 10, 2); $t->boolean('is_free')->default(false);
            $t->boolean('is_active')->default(true); $t->integer('sort_order')->default(0);
            $t->timestamps(); $t->softDeletes(); $t->unique(['magazine_id', 'slug']);
        });

        Schema::create('subscriptions', function (Blueprint $t) {
            $t->id(); $t->string('subscription_number')->unique();
            $t->foreignId('client_id')->constrained()->cascadeOnDelete();
            $t->foreignId('payer_client_id')->nullable()->constrained('clients')->nullOnDelete();
            $t->foreignId('magazine_id')->constrained()->cascadeOnDelete();
            $t->foreignId('subscription_plan_id')->constrained()->cascadeOnDelete();
            $t->enum('status', ['active','expired','suspended','cancelled','pending','trial'])->default('pending');
            $t->enum('support_type', ['paper','digital','combined']); $t->enum('mode', ['duration','issues']);
            $t->date('start_date'); $t->date('end_date')->nullable();
            $t->integer('issues_total')->nullable(); $t->integer('issues_delivered')->default(0);
            $t->integer('first_issue_number')->nullable(); $t->integer('last_issue_number')->nullable();
            $t->decimal('amount_paid', 10, 2)->default(0);
            $t->enum('payment_method', ['card','sepa','check','transfer','free'])->nullable();
            $t->string('payment_reference')->nullable(); $t->boolean('auto_renew')->default(false);
            $t->text('notes')->nullable(); $t->json('shipping_address')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps(); $t->softDeletes();
            $t->index('status'); $t->index(['client_id','status']); $t->index('end_date');
        });

        Schema::create('magazine_issues', function (Blueprint $t) {
            $t->id(); $t->foreignId('magazine_id')->constrained()->cascadeOnDelete();
            $t->string('issue_number'); $t->string('title'); $t->text('description')->nullable();
            $t->date('publication_date'); $t->string('month_label')->nullable();
            $t->string('cover_image')->nullable(); $t->string('thumbnail_path')->nullable();
            $t->string('pdf_file')->nullable(); $t->unsignedBigInteger('file_size')->nullable();
            $t->integer('page_count')->nullable(); $t->boolean('is_published')->default(false);
            $t->timestamps(); $t->softDeletes();
            $t->unique(['magazine_id','issue_number']); $t->index('publication_date');
        });

        Schema::create('invoices', function (Blueprint $t) {
            $t->id(); $t->string('invoice_number')->unique();
            $t->foreignId('client_id')->constrained()->cascadeOnDelete();
            $t->foreignId('payer_client_id')->nullable()->constrained('clients')->nullOnDelete();
            $t->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $t->date('invoice_date'); $t->date('due_date');
            $t->decimal('subtotal', 10, 2); $t->decimal('tax_rate', 5, 2)->default(2.10);
            $t->decimal('tax_amount', 10, 2); $t->decimal('total', 10, 2);
            $t->enum('status', ['draft','sent','paid','overdue','cancelled'])->default('draft');
            $t->enum('payment_method', ['card','sepa','check','transfer','free'])->nullable();
            $t->string('payment_reference')->nullable(); $t->date('paid_at')->nullable();
            $t->text('notes')->nullable(); $t->json('facturx_data')->nullable();
            $t->timestamps(); $t->softDeletes();
        });

        Schema::create('invoice_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $t->string('description'); $t->integer('quantity')->default(1);
            $t->decimal('unit_price', 10, 2); $t->decimal('total', 10, 2); $t->timestamps();
        });

        Schema::create('payments', function (Blueprint $t) {
            $t->id(); $t->string('payment_number')->unique();
            $t->foreignId('client_id')->constrained()->cascadeOnDelete();
            $t->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $t->decimal('amount', 10, 2);
            $t->enum('method', ['card','sepa','check','transfer','free','cash']);
            $t->enum('status', ['pending','completed','failed','refunded'])->default('pending');
            $t->string('reference')->nullable(); $t->string('stripe_payment_id')->nullable();
            $t->date('payment_date'); $t->text('notes')->nullable();
            $t->timestamps(); $t->softDeletes();
        });

        Schema::create('contacts', function (Blueprint $t) {
            $t->id(); $t->string('first_name'); $t->string('last_name'); $t->string('email');
            $t->string('phone')->nullable(); $t->string('subject'); $t->text('message');
            $t->enum('status', ['new','read','replied','closed'])->default('new');
            $t->text('admin_notes')->nullable();
            $t->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('replied_at')->nullable(); $t->timestamps(); $t->softDeletes(); $t->index('status');
        });

        Schema::create('postal_routings', function (Blueprint $t) {
            $t->id(); $t->foreignId('magazine_issue_id')->constrained()->cascadeOnDelete();
            $t->string('file_path')->nullable(); $t->integer('total_recipients')->default(0);
            $t->enum('status', ['pending','generated','sent','error'])->default('pending');
            $t->timestamp('generated_at')->nullable(); $t->timestamp('sent_at')->nullable();
            $t->text('notes')->nullable(); $t->timestamps();
        });

        Schema::create('import_batches', function (Blueprint $t) {
            $t->id(); $t->string('filename');
            $t->enum('type', ['clients','subscriptions','combined'])->default('combined');
            $t->enum('status', ['pending','processing','completed','failed'])->default('pending');
            $t->integer('total_rows')->default(0); $t->integer('processed_rows')->default(0);
            $t->integer('success_rows')->default(0); $t->integer('error_rows')->default(0);
            $t->json('errors')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('import_mappings', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->text('description')->nullable();
            $t->json('mapping'); $t->json('options')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('custom_field_definitions', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('slug')->unique(); $t->string('label');
            $t->enum('type', ['text','textarea','number','date','select','boolean','email','url','phone']);
            $t->json('options')->nullable(); $t->string('group')->default('general');
            $t->boolean('required')->default(false); $t->boolean('is_active')->default(true);
            $t->integer('sort_order')->default(0); $t->text('description')->nullable();
            $t->string('default_value')->nullable(); $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['custom_field_definitions','import_mappings','import_batches','postal_routings','contacts','payments','invoice_lines','invoices','magazine_issues','subscriptions','subscription_plans','payers'] as $t) Schema::dropIfExists($t);
        Schema::table('users', function (Blueprint $t) { $t->dropForeign(['client_id']); $t->dropColumn('client_id'); });
        Schema::dropIfExists('clients'); Schema::dropIfExists('magazines');
        Schema::table('users', function (Blueprint $t) { $t->dropColumn(['first_name','last_name','phone','is_active']); $t->dropSoftDeletes(); });
        foreach (['failed_jobs','job_batches','jobs','cache_locks','cache'] as $t) Schema::dropIfExists($t);
    }
};

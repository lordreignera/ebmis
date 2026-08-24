<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bulk_sms')) {
            return;
        }

        Schema::table('bulk_sms', function (Blueprint $table) {
            if (! Schema::hasColumn('bulk_sms', 'title')) {
                $table->string('title')->nullable();
            }

            if (! Schema::hasColumn('bulk_sms', 'recipient_type')) {
                $table->enum('recipient_type', ['all', 'group', 'individual'])->default('all');
            }

            if (! Schema::hasColumn('bulk_sms', 'recipient_group')) {
                $table->string('recipient_group')->nullable();
            }

            if (! Schema::hasColumn('bulk_sms', 'recipients_count')) {
                $table->integer('recipients_count')->default(0);
            }

            if (! Schema::hasColumn('bulk_sms', 'successful_count')) {
                $table->integer('successful_count')->default(0);
            }

            if (! Schema::hasColumn('bulk_sms', 'failed_count')) {
                $table->integer('failed_count')->default(0);
            }

            if (! Schema::hasColumn('bulk_sms', 'status')) {
                $table->enum('status', ['pending', 'scheduled', 'sending', 'completed', 'failed'])->default('pending');
            }

            if (! Schema::hasColumn('bulk_sms', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable();
            }

            if (! Schema::hasColumn('bulk_sms', 'completed_at')) {
                $table->timestamp('completed_at')->nullable();
            }

            if (! Schema::hasColumn('bulk_sms', 'sent_by')) {
                $table->unsignedBigInteger('sent_by')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bulk_sms')) {
            return;
        }

        Schema::table('bulk_sms', function (Blueprint $table) {
            foreach ([
                'title',
                'recipient_type',
                'recipient_group',
                'recipients_count',
                'successful_count',
                'failed_count',
                'status',
                'scheduled_at',
                'completed_at',
                'sent_by',
            ] as $column) {
                if (Schema::hasColumn('bulk_sms', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cabang_hierarchy')) {
            Schema::create('cabang_hierarchy', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cabang_id')->constrained('cabang')->onDelete('cascade');
                $table->foreignId('manager_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('supervisor_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique('cabang_id');
                $table->unique('supervisor_user_id');
            });
        }

        if (! Schema::hasTable('supervisor_hierarchy')) {
            Schema::create('supervisor_hierarchy', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supervisor_user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('cabang_id')->constrained('cabang')->onDelete('cascade');
                $table->foreignId('manager_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique('supervisor_user_id');
                $table->unique('cabang_id');
                $table->index('manager_user_id');
            });
        }

        if (! Schema::hasTable('kasir_hierarchy')) {
            Schema::create('kasir_hierarchy', function (Blueprint $table) {
                $table->id();
                $table->foreignId('kasir_user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('cabang_id')->constrained('cabang')->onDelete('cascade');
                $table->foreignId('supervisor_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('manager_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique('kasir_user_id');
                $table->index('cabang_id');
                $table->index(['supervisor_user_id', 'manager_user_id']);
            });
        }

        if (! Schema::hasTable('assignment_histories')) {
            Schema::create('assignment_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('actor_user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('subject_user_id')->constrained('users')->onDelete('cascade');
                $table->string('subject_role', 20);
                $table->string('action', 30);
                $table->foreignId('from_cabang_id')->nullable()->constrained('cabang')->nullOnDelete();
                $table->foreignId('to_cabang_id')->nullable()->constrained('cabang')->nullOnDelete();
                $table->foreignId('from_manager_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('to_manager_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('from_supervisor_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('to_supervisor_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->json('payload')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['actor_user_id', 'created_at']);
                $table->index(['subject_user_id', 'created_at']);
                $table->index(['subject_role', 'action']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('assignment_histories')) {
            Schema::drop('assignment_histories');
        }
        if (Schema::hasTable('kasir_hierarchy')) {
            Schema::drop('kasir_hierarchy');
        }
        if (Schema::hasTable('supervisor_hierarchy')) {
            Schema::drop('supervisor_hierarchy');
        }
        if (Schema::hasTable('cabang_hierarchy')) {
            Schema::drop('cabang_hierarchy');
        }
    }
};

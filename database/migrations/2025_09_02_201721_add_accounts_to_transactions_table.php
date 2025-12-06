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
        Schema::table('transactions', function (Blueprint $table) {
            // Add account_id for the account where transaction occurs
            $table->foreignId('account_id')->after('user_id')->constrained()->onDelete('cascade');
            
            // Add transfer_account_id for transfer transactions
            $table->foreignId('transfer_account_id')->nullable()->after('account_id')->constrained('accounts')->onDelete('cascade');
            
            // Add indexes for better performance
            $table->index(['user_id', 'account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropForeign(['transfer_account_id']);
            $table->dropIndex(['user_id', 'account_id']);
            $table->dropColumn(['account_id', 'transfer_account_id']);
        });
    }
};

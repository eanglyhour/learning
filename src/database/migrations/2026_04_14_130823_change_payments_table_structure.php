<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // drop old column
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
            // add correct column
            $table->foreignId('order_id')
                ->after('id')
                ->constrained()
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {

            $table->dropForeign(['order_id']);
            $table->dropColumn('order_id');

            $table->foreignId('product_id')
                ->constrained()
                ->onDelete('cascade');
        });
    }
};
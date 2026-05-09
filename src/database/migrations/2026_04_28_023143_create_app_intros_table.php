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
        Schema::create('app_intros', function (Blueprint $table) {
            // String text unsignedInterger
            $table->id();
            $table->String("title");
            $table->text("description");
            $table->String("image");
            $table->String("button_text");
            $table->boolean("is_active")->default(true);
            $table->unsignedInteger("order_no"); // default 1 ....
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_intros');
    }
};

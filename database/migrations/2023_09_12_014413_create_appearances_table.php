<?php

use App\Enums\Font;
use App\Enums\MaxContentWidth;
use App\Enums\ModalWidth;
use App\Enums\PrimaryColor;
use App\Enums\RecordsPerPage;
use App\Enums\TableSortDirection;
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
        Schema::create('appearances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('primary_color')->default(PrimaryColor::DEFAULT);
            $table->string('font')->default(Font::DEFAULT);
            $table->string('max_content_width')->default(MaxContentWidth::DEFAULT);
            $table->string('modal_width')->default(ModalWidth::DEFAULT);
            $table->string('table_sort_direction')->default(TableSortDirection::DEFAULT);
            $table->unsignedTinyInteger('records_per_page')->default(RecordsPerPage::DEFAULT);
            $table->boolean('has_top_navigation')->default(false);
            $table->boolean('is_table_striped')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appearances');
    }
};

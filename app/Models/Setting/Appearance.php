<?php

namespace App\Models\Setting;

use App\Enums\{Font, MaxContentWidth, ModalWidth, PrimaryColor, RecordsPerPage, TableSortDirection};
use App\Traits\{Blamable, CompanyOwned};
use Database\Factories\Setting\AppearanceFactory;
use Illuminate\Database\Eloquent\Factories\{Factory, HasFactory};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Wallo\FilamentCompanies\FilamentCompanies;

class Appearance extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $table = 'appearances';

    protected $fillable = [
        'company_id',
        'primary_color',
        'font',
        'max_content_width',
        'modal_width',
        'table_sort_direction',
        'records_per_page',
        'has_top_navigation',
        'is_table_striped',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'primary_color' => PrimaryColor::class,
        'font' => Font::class,
        'max_content_width' => MaxContentWidth::class,
        'modal_width' => ModalWidth::class,
        'table_sort_direction' => TableSortDirection::class,
        'records_per_page' => RecordsPerPage::class,
        'has_top_navigation' => 'boolean',
        'is_table_striped' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::companyModel(), 'company_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'updated_by');
    }

    protected static function newFactory(): Factory
    {
        return AppearanceFactory::new();
    }
}

<?php
// app/Models/Bundles.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Bundles extends Model
{
    protected $fillable = [
        'title',
        'total_price',
        'discount_price',
        'discount_end_date',
        'featured_image',
        'bundle_type',
        'product_model',
        'system_capacity_display',
        'detailed_description',
        'what_is_inside_bundle_text',
        'what_bundle_powers_text',
        'backup_time_description',
        'total_load',
        'inver_rating',
        'total_output',
    ];

    protected $appends = ['featured_image_url'];

    protected $table = 'bundles'; // <- avoids any pluralization surprises

    public function bundleItems()
    {
        return $this->hasMany(BundleItems::class, 'bundle_id');
    }

    public function customServices()
    {
        return $this->hasMany(CustomService::class, 'bundle_id');
    }

    public function bundleMaterials()
    {
        return $this->hasMany(BundleMaterial::class, 'bundle_id');
    }

    public function customAppliances()
    {
        return $this->hasMany(BundleCustomAppliance::class, 'bundle_id');
    }

    public function getFeaturedImageUrlAttribute(): ?string
    {
        if (!$this->featured_image) return null;
        if (Str::startsWith($this->featured_image, ['http://','https://','/storage/'])) {
            return $this->featured_image;
        }
        return Storage::url($this->featured_image);
    }
}
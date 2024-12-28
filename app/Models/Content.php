<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Content extends Model
{
    /** @use HasFactory<\Database\Factories\ContentFactory> */
    use HasFactory, HasSlug;

    protected $fillable = [
        'title',
        'description',
        'body',
        'cover',
        'type'
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    /**
     * Scopes
     */
    public function scopeActiveVideos($query)
    {
        return $this->videos()
            ->whereNotNull('code')
            ->whereisProcessed(true);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }
}

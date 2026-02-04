<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Article extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'slug',
        'title',
        'content',
        'content_rewrite',
        'category',
        'label',
        'is_reviewed',
        'published_at',
        'created_by',
        'updated_by',
        'edited_by',
        'published_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_reviewed' => 'boolean',
        'published_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug from title if not provided
        static::creating(function ($article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->title);

                // Ensure unique slug
                $count = 1;
                $originalSlug = $article->slug;
                while (static::where('slug', $article->slug)->exists()) {
                    $article->slug = $originalSlug . '-' . $count++;
                }
            }
        });
    }

    /**
     * Scope: Only published articles
     */
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope: Only reviewed articles
     */
    public function scopeReviewed($query)
    {
        return $query->where('is_reviewed', true);
    }

    /**
     * Scope: Only draft articles
     */
    public function scopeDraft($query)
    {
        return $query->whereNull('published_at');
    }

    /**
     * Scope: Filter by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', 'like', '%' . $category . '%');
    }

    /**
     * Scope: Filter by label
     */
    public function scopeByLabel($query, $label)
    {
        return $query->where('label', 'like', '%' . $label . '%');
    }

    /**
     * Get categories as array
     */
    public function getCategoryListAttribute()
    {
        if (empty($this->category)) {
            return [];
        }
        return array_map('trim', explode(',', $this->category));
    }

    /**
     * Get labels as array
     */
    public function getLabelListAttribute()
    {
        if (empty($this->label)) {
            return [];
        }
        return array_map('trim', explode(',', $this->label));
    }

    /**
     * Set categories from array
     */
    public function setCategoryAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['category'] = implode(', ', $value);
        } else {
            $this->attributes['category'] = $value;
        }
    }

    /**
     * Set labels from array
     */
    public function setLabelAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['label'] = implode(', ', $value);
        } else {
            $this->attributes['label'] = $value;
        }
    }

    /**
     * Check if article is published
     */
    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }

    /**
     * Check if article is draft
     */
    public function isDraft(): bool
    {
        return $this->published_at === null;
    }
}

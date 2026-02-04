<?php

namespace App\Services;

use App\Models\Article;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ArticleService
{
    /**
     * Create a new article
     */
    public function createArticle(array $data, User $user): Article
    {
        return DB::transaction(function () use ($data, $user) {
            $article = new Article($data);
            $article->created_by = $user->name;
            $article->updated_by = $user->name;
            $article->save();

            return $article;
        });
    }

    /**
     * Update an existing article
     */
    public function updateArticle(Article $article, array $data, User $user): Article
    {
        return DB::transaction(function () use ($article, $data, $user) {
            $article->fill($data);
            $article->updated_by = $user->name;
            $article->edited_by = $user->name;
            $article->save();

            return $article;
        });
    }

    /**
     * Publish an article
     */
    public function publishArticle(Article $article, User $user): Article
    {
        if (!$article->is_reviewed) {
            throw new \Exception('Article must be reviewed before publishing');
        }

        return DB::transaction(function () use ($article, $user) {
            $article->published_at = now();
            $article->published_by = $user->name;
            $article->updated_by = $user->name;
            $article->save();

            return $article;
        });
    }

    /**
     * Unpublish an article
     */
    public function unpublishArticle(Article $article, User $user): Article
    {
        return DB::transaction(function () use ($article, $user) {
            $article->published_at = null;
            $article->published_by = null;
            $article->updated_by = $user->name;
            $article->save();

            return $article;
        });
    }

    /**
     * Review an article
     */
    public function reviewArticle(Article $article, User $user, bool $approved): Article
    {
        return DB::transaction(function () use ($article, $user, $approved) {
            $article->is_reviewed = $approved;
            $article->updated_by = $user->name;
            $article->save();

            return $article;
        });
    }

    /**
     * Soft delete an article
     */
    public function deleteArticle(Article $article, User $user): bool
    {
        return DB::transaction(function () use ($article, $user) {
            $article->deleted_by = $user->name;
            $article->save();

            return $article->delete();
        });
    }

    /**
     * Restore a soft deleted article
     */
    public function restoreArticle(Article $article, User $user): Article
    {
        return DB::transaction(function () use ($article, $user) {
            $article->restore();
            $article->deleted_by = null;
            $article->updated_by = $user->name;
            $article->save();

            return $article;
        });
    }

    /**
     * Search articles with filters
     */
    public function searchArticles(array $filters = [], int $limit = 10)
    {
        $query = Article::query();

        // Filter by category
        if (!empty($filters['category'])) {
            $query->byCategory($filters['category']);
        }

        // Filter by label
        if (!empty($filters['label'])) {
            $query->byLabel($filters['label']);
        }

        // Filter by review status
        if (isset($filters['is_reviewed'])) {
            $query->where('is_reviewed', $filters['is_reviewed']);
        }

        // Filter by status (published/draft)
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'PUBLISHED') {
                $query->published();
            } elseif ($filters['status'] === 'DRAFT') {
                $query->draft();
            }
        }

        // Search in title and content
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('content', 'like', '%' . $search . '%');
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($limit);
    }

    /**
     * Find article by ID or slug
     */
    public function findArticle($idOrSlug): ?Article
    {
        if (is_numeric($idOrSlug)) {
            return Article::find($idOrSlug);
        }

        return Article::where('slug', $idOrSlug)->first();
    }
}

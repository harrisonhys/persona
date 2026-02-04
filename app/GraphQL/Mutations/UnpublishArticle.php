<?php

namespace App\GraphQL\Mutations;

use App\Models\Article;
use App\Services\ArticleService;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class UnpublishArticle
{
    protected ArticleService $articleService;

    public function __construct(ArticleService $articleService)
    {
        $this->articleService = $articleService;
    }

    public function __invoke($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Article
    {
        /** @var \App\Models\User $user */
        $user = $context->user();
        $article = Article::findOrFail($args['id']);

        return $this->articleService->unpublishArticle($article, $user);
    }
}

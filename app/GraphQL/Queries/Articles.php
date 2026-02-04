<?php

namespace App\GraphQL\Queries;

use App\Services\ArticleService;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class Articles
{
    protected ArticleService $articleService;

    public function __construct(ArticleService $articleService)
    {
        $this->articleService = $articleService;
    }

    public function __invoke($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $filters = $args['filters'] ?? [];
        $limit = $args['first'] ?? 10;

        return $this->articleService->searchArticles($filters, $limit);
    }
}

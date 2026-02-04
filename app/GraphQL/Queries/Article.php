<?php

namespace App\GraphQL\Queries;

use App\Services\ArticleService;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class Article
{
    protected ArticleService $articleService;

    public function __construct(ArticleService $articleService)
    {
        $this->articleService = $articleService;
    }

    public function __invoke($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        if (isset($args['id'])) {
            return $this->articleService->findArticle($args['id']);
        }

        if (isset($args['slug'])) {
            return $this->articleService->findArticle($args['slug']);
        }

        return null;
    }
}

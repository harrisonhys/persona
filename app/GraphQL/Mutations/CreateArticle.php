<?php

namespace App\GraphQL\Mutations;

use App\Models\Article;
use App\Services\ArticleService;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class CreateArticle
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

        return $this->articleService->createArticle($args['input'], $user);
    }
}

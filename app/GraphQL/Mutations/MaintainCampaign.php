<?php

namespace App\GraphQL\Mutations;

use App\Services\CampaignService;

final class MaintainCampaign
{
    public function __construct(
        protected CampaignService $campaignService
    ) {
    }

    /**
     * @param  null  $_
     * @param  array{}  $args
     */
    public function __invoke($_, array $args)
    {
        return $this->campaignService->maintain(
            $args['input'],
            $args['id'] ?? null
        );
    }
}

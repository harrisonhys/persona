<?php

namespace Tests\Feature\GraphQL;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class CampaignQueryTest extends TestCase
{
    use RefreshDatabase, MakesGraphQLRequests;

    public function test_can_paginate_campaigns(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user); // Default guard

        Campaign::factory()->count(15)->create();

        $response = $this->graphQL('
            query {
                campaigns(first: 5) {
                    data {
                        id
                        title
                    }
                    paginatorInfo {
                        count
                        total
                        hasMorePages
                    }
                }
            }
        ');

        $response->assertJson([
            'data' => [
                'campaigns' => [
                    'paginatorInfo' => [
                        'count' => 5,
                        'total' => 15,
                        'hasMorePages' => true,
                    ]
                ]
            ]
        ]);
    }
}

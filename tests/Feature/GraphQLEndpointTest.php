<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GraphQLEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_maintain_campaign_mutation()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/graphql', [
            'query' => '
                mutation {
                    maintainCampaign(input: {
                        title: "New Campaign"
                        status: ACTIVE
                        metadata_json: "{\"key\":\"value\"}"
                    }) {
                        id
                        title
                        status
                        metadata
                    }
                }
            '
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.maintainCampaign.title', 'New Campaign');
        $response->assertJsonPath('data.maintainCampaign.metadata', '{"key":"value"}');
    }
}

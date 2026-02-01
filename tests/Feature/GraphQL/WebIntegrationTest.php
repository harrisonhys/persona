<?php

namespace Tests\Feature\GraphQL;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class WebIntegrationTest extends TestCase
{
    use RefreshDatabase, MakesGraphQLRequests;

    public function test_guest_cannot_access_protected_query(): void
    {
        $this->graphQL('
            query {
                users {
                    id
                }
            }
        ')->assertGraphQLErrorMessage('Unauthenticated.');
    }

    public function test_spa_user_can_access_query_via_session(): void
    {
        $user = User::factory()->create();

        // Simulate a Web Login (Cookies/Session)
        $this->actingAs($user, 'web');

        $response = $this->graphQL('
            query {
                users {
                    id
                    email
                }
            }
        ');
        $response->assertJsonStructure([
            'data' => [
                'users' => [
                    ['id', 'email']
                ]
            ]
        ]);
    }
}

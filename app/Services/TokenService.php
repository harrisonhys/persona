<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Carbon\Carbon;

class TokenService
{
    /**
     * Create a new token with metadata
     */
    public function createToken(
        User $user,
        string $name,
        array $abilities = ['*'],
        ?int $expiresInDays = null,
        array $metadata = []
    ): array {
        $token = $user->createToken($name, $abilities);

        // Update with metadata
        $accessToken = $token->accessToken;
        $accessToken->created_by = auth()->id() ?? $user->id;
        $accessToken->metadata = array_merge([
            'created_at' => now()->toIso8601String(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $metadata);

        if ($expiresInDays) {
            $accessToken->expires_at = now()->addDays($expiresInDays);
        }

        $accessToken->save();

        return [
            'plainTextToken' => $token->plainTextToken,
            'accessToken' => $accessToken,
        ];
    }

    /**
     * Revoke token by ID or name
     */
    public function revokeToken(User $user, ?int $tokenId = null, ?string $tokenName = null): bool
    {
        $query = $user->tokens();

        if ($tokenId) {
            $query->where('id', $tokenId);
        } elseif ($tokenName) {
            $query->where('name', $tokenName);
        } else {
            return false;
        }

        return $query->delete() > 0;
    }

    /**
     * Rotate token - create new and revoke old
     */
    public function rotateToken(
        User $user,
        string $oldTokenName,
        ?string $newTokenName = null,
        array $abilities = ['*'],
        ?int $expiresInDays = null,
        array $metadata = []
    ): array {
        return DB::transaction(function () use ($user, $oldTokenName, $newTokenName, $abilities, $expiresInDays, $metadata) {
            // Create new token
            $newName = $newTokenName ?? $oldTokenName . '-' . now()->format('Ymd');
            $result = $this->createToken($user, $newName, $abilities, $expiresInDays, array_merge($metadata, [
                'rotated_from' => $oldTokenName,
            ]));

            // Revoke old token
            $this->revokeToken($user, null, $oldTokenName);

            return $result;
        });
    }

    /**
     * List all tokens for a user
     */
    public function listTokens(User $user, bool $includeExpired = false): \Illuminate\Support\Collection
    {
        $query = $user->tokens();

        if (!$includeExpired) {
            $query->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get token information
     */
    public function getTokenInfo(int $tokenId): ?PersonalAccessToken
    {
        return PersonalAccessToken::find($tokenId);
    }

    /**
     * Check for expiring tokens
     */
    public function getExpiringTokens(int $daysThreshold = 7): \Illuminate\Support\Collection
    {
        return PersonalAccessToken::whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays($daysThreshold))
            ->get();
    }

    /**
     * Get expired tokens
     */
    public function getExpiredTokens(): \Illuminate\Support\Collection
    {
        return PersonalAccessToken::whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();
    }

    /**
     * Revoke expired tokens
     */
    public function revokeExpiredTokens(): int
    {
        return PersonalAccessToken::whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->delete();
    }

    /**
     * Update last used timestamp
     */
    public function updateLastUsed(PersonalAccessToken $token): void
    {
        $token->last_used_at = now();
        $token->save();
    }

    /**
     * Get tokens not used for X days
     */
    public function getUnusedTokens(int $days = 30): \Illuminate\Support\Collection
    {
        return PersonalAccessToken::where(function ($query) use ($days) {
            $query->whereNull('last_used_at')
                ->where('created_at', '<=', now()->subDays($days));
        })->orWhere(function ($query) use ($days) {
            $query->whereNotNull('last_used_at')
                ->where('last_used_at', '<=', now()->subDays($days));
        })->get();
    }
}

<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TokenService;
use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;

class ManageTokenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'token:manage 
                            {action : Action to perform (generate|list|revoke|rotate|info|cleanup)}
                            {--user= : User email or ID}
                            {--name= : Token name}
                            {--id= : Token ID}
                            {--abilities=* : Token abilities (default: *)}
                            {--expires= : Expiration in days}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage API tokens for authentication';

    protected TokenService $tokenService;

    public function __construct(TokenService $tokenService)
    {
        parent::__construct();
        $this->tokenService = $tokenService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        return match ($action) {
            'generate' => $this->generateToken(),
            'list' => $this->listTokens(),
            'revoke' => $this->revokeToken(),
            'rotate' => $this->rotateToken(),
            'info' => $this->tokenInfo(),
            'cleanup' => $this->cleanupTokens(),
            default => $this->error("Unknown action: {$action}. Use: generate, list, revoke, rotate, info, or cleanup"),
        };
    }

    protected function generateToken()
    {
        $this->info('🔐 Generate New API Token');
        $this->newLine();

        // Get user
        $user = $this->getUser();
        if (!$user) {
            return 1;
        }

        // Get token name
        $name = $this->option('name') ?? $this->ask('Token name', 'api-token-' . now()->format('Ymd'));

        // Get abilities
        $abilities = $this->option('abilities');
        if (empty($abilities)) {
            if ($this->confirm('Grant all abilities?', true)) {
                $abilities = ['*'];
            } else {
                $abilitiesInput = $this->ask('Enter abilities (comma-separated)', 'campaign:read,campaign:write');
                $abilities = array_map('trim', explode(',', $abilitiesInput));
            }
        }

        // Get expiration
        $expires = $this->option('expires');
        if (!$expires && $this->confirm('Set expiration date?', false)) {
            $expires = (int) $this->ask('Expires in how many days?', '90');
        }

        // Get metadata
        $metadata = [];
        if ($this->confirm('Add metadata?', false)) {
            $purpose = $this->ask('Purpose/Description');
            $environment = $this->choice('Environment', ['production', 'staging', 'development'], 0);

            $metadata = [
                'purpose' => $purpose,
                'environment' => $environment,
            ];
        }

        // Create token
        $this->info('Creating token...');
        $result = $this->tokenService->createToken($user, $name, $abilities, $expires, $metadata);

        $this->newLine();
        $this->line('✅ <fg=green>Token created successfully!</>');
        $this->newLine();

        $this->warn('⚠️  IMPORTANT: Copy this token now. It will not be shown again!');
        $this->newLine();

        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line("<fg=cyan>{$result['plainTextToken']}</>");
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $this->newLine();
        $this->table(
            ['Property', 'Value'],
            [
                ['Token ID', $result['accessToken']->id],
                ['Name', $result['accessToken']->name],
                ['User', $user->email],
                ['Abilities', implode(', ', $result['accessToken']->abilities)],
                ['Expires', $result['accessToken']->expires_at?->format('Y-m-d H:i:s') ?? 'Never'],
                ['Created', $result['accessToken']->created_at->format('Y-m-d H:i:s')],
            ]
        );

        return 0;
    }

    protected function listTokens()
    {
        $this->info('📋 List API Tokens');
        $this->newLine();

        $user = $this->getUser();
        if (!$user) {
            return 1;
        }

        $includeExpired = $this->option('force') || $this->confirm('Include expired tokens?', false);
        $tokens = $this->tokenService->listTokens($user, $includeExpired);

        if ($tokens->isEmpty()) {
            $this->warn('No tokens found for this user.');
            return 0;
        }

        $rows = $tokens->map(function ($token) {
            $status = '🟢 Active';
            if ($token->expires_at && $token->expires_at->isPast()) {
                $status = '🔴 Expired';
            } elseif ($token->expires_at && $token->expires_at->diffInDays() <= 7) {
                $status = '🟡 Expiring Soon';
            }

            return [
                $token->id,
                $token->name,
                $status,
                implode(', ', $token->abilities ?? ['*']),
                $token->last_used_at?->diffForHumans() ?? 'Never',
                $token->expires_at?->format('Y-m-d') ?? 'Never',
                $token->created_at->format('Y-m-d'),
            ];
        });

        $this->table(
            ['ID', 'Name', 'Status', 'Abilities', 'Last Used', 'Expires', 'Created'],
            $rows
        );

        $this->newLine();
        $this->info("Total: {$tokens->count()} token(s)");

        return 0;
    }

    protected function revokeToken()
    {
        $this->warn('🗑️  Revoke API Token');
        $this->newLine();

        $user = $this->getUser();
        if (!$user) {
            return 1;
        }

        $tokenId = $this->option('id');
        $tokenName = $this->option('name');

        if (!$tokenId && !$tokenName) {
            // Show list and ask which to revoke
            $tokens = $this->tokenService->listTokens($user, true);

            if ($tokens->isEmpty()) {
                $this->warn('No tokens found.');
                return 0;
            }

            $choices = $tokens->mapWithKeys(function ($token) {
                return [$token->id => "{$token->name} (ID: {$token->id})"];
            })->toArray();

            $selectedId = $this->choice('Select token to revoke', $choices);
            $tokenId = $selectedId;
        }

        // Get token info for confirmation
        $token = $tokenId
            ? PersonalAccessToken::find($tokenId)
            : $user->tokens()->where('name', $tokenName)->first();

        if (!$token) {
            $this->error('Token not found.');
            return 1;
        }

        $this->table(
            ['Property', 'Value'],
            [
                ['ID', $token->id],
                ['Name', $token->name],
                ['Created', $token->created_at->format('Y-m-d H:i:s')],
                ['Last Used', $token->last_used_at?->format('Y-m-d H:i:s') ?? 'Never'],
            ]
        );

        if (!$this->option('force') && !$this->confirm('Are you sure you want to revoke this token?', false)) {
            $this->info('Cancelled.');
            return 0;
        }

        $success = $this->tokenService->revokeToken($user, $tokenId, $tokenName);

        if ($success) {
            $this->info('✅ Token revoked successfully.');
        } else {
            $this->error('❌ Failed to revoke token.');
            return 1;
        }

        return 0;
    }

    protected function rotateToken()
    {
        $this->info('🔄 Rotate API Token');
        $this->newLine();

        $user = $this->getUser();
        if (!$user) {
            return 1;
        }

        $oldTokenName = $this->option('name') ?? $this->ask('Old token name to rotate');

        $oldToken = $user->tokens()->where('name', $oldTokenName)->first();
        if (!$oldToken) {
            $this->error("Token '{$oldTokenName}' not found.");
            return 1;
        }

        $newTokenName = $this->ask('New token name', $oldTokenName . '-' . now()->format('Ymd'));

        $abilities = $oldToken->abilities ?? ['*'];
        $expires = $oldToken->expires_at ? $oldToken->expires_at->diffInDays(now()) : null;

        if (!$this->option('force') && !$this->confirm('This will create a new token and revoke the old one. Continue?', true)) {
            $this->info('Cancelled.');
            return 0;
        }

        $result = $this->tokenService->rotateToken($user, $oldTokenName, $newTokenName, $abilities, $expires);

        $this->newLine();
        $this->line('✅ <fg=green>Token rotated successfully!</>');
        $this->newLine();

        $this->warn('⚠️  IMPORTANT: Update your applications with this new token!');
        $this->newLine();

        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line("<fg=cyan>{$result['plainTextToken']}</>");
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        return 0;
    }

    protected function tokenInfo()
    {
        $this->info('ℹ️  Token Information');
        $this->newLine();

        $tokenId = $this->option('id') ?? $this->ask('Token ID');
        $token = $this->tokenService->getTokenInfo($tokenId);

        if (!$token) {
            $this->error('Token not found.');
            return 1;
        }

        $user = User::find($token->tokenable_id);
        $metadata = $token->metadata ?? [];

        $this->table(
            ['Property', 'Value'],
            [
                ['ID', $token->id],
                ['Name', $token->name],
                ['User', $user?->email ?? 'N/A'],
                ['Abilities', implode(', ', $token->abilities ?? ['*'])],
                ['Created', $token->created_at->format('Y-m-d H:i:s')],
                ['Last Used', $token->last_used_at?->format('Y-m-d H:i:s') ?? 'Never'],
                ['Expires', $token->expires_at?->format('Y-m-d H:i:s') ?? 'Never'],
                ['Created By', $token->created_by ?? 'N/A'],
            ]
        );

        if (!empty($metadata)) {
            $this->newLine();
            $this->info('Metadata:');
            $this->table(
                ['Key', 'Value'],
                collect($metadata)->map(fn($value, $key) => [$key, is_array($value) ? json_encode($value) : $value])
            );
        }

        return 0;
    }

    protected function cleanupTokens()
    {
        $this->warn('🧹 Cleanup Tokens');
        $this->newLine();

        $this->info('Checking for expired tokens...');
        $expiredTokens = $this->tokenService->getExpiredTokens();

        if ($expiredTokens->isNotEmpty()) {
            $this->warn("Found {$expiredTokens->count()} expired token(s).");

            if ($this->option('force') || $this->confirm('Revoke all expired tokens?', true)) {
                $count = $this->tokenService->revokeExpiredTokens();
                $this->info("✅ Revoked {$count} expired token(s).");
            }
        } else {
            $this->info('No expired tokens found.');
        }

        $this->newLine();
        $this->info('Checking for unused tokens...');
        $unusedTokens = $this->tokenService->getUnusedTokens(30);

        if ($unusedTokens->isNotEmpty()) {
            $this->warn("Found {$unusedTokens->count()} token(s) not used in 30+ days.");

            $rows = $unusedTokens->map(fn($token) => [
                $token->id,
                $token->name,
                $token->last_used_at?->format('Y-m-d') ?? 'Never',
            ]);

            $this->table(['ID', 'Name', 'Last Used'], $rows);

            $this->info('Consider revoking unused tokens manually with: php artisan token:manage revoke');
        } else {
            $this->info('No unused tokens found.');
        }

        $this->newLine();
        $this->info('Checking for expiring tokens...');
        $expiringTokens = $this->tokenService->getExpiringTokens(7);

        if ($expiringTokens->isNotEmpty()) {
            $this->warn("Found {$expiringTokens->count()} token(s) expiring within 7 days.");

            $rows = $expiringTokens->map(fn($token) => [
                $token->id,
                $token->name,
                $token->expires_at->format('Y-m-d'),
                $token->expires_at->diffForHumans(),
            ]);

            $this->table(['ID', 'Name', 'Expires On', 'Expires'], $rows);

            $this->info('Consider rotating these tokens with: php artisan token:manage rotate');
        } else {
            $this->info('No tokens expiring soon.');
        }

        return 0;
    }

    protected function getUser(): ?User
    {
        $userInput = $this->option('user');

        if (!$userInput) {
            $userInput = $this->ask('User email or ID');
        }

        // Try to find by email first, then by ID
        $user = User::where('email', $userInput)->first();

        if (!$user && is_numeric($userInput)) {
            $user = User::find($userInput);
        }

        if (!$user) {
            $this->error("User not found: {$userInput}");
            return null;
        }

        return $user;
    }
}

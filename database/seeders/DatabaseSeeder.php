<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $user = \App\Models\User::firstOrCreate(
            ['email' => 'n8n@bot.com'],
            ['name' => 'n8n Bot', 'password' => bcrypt('password')]
        );

        echo "n8n Token: " . $user->createToken('n8n-setup')->plainTextToken . "\n";
    }
}

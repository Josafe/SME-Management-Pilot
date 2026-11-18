<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, bool}>
     */
    public static function environments(): array
    {
        return [
            'local' => ['local', true],
            'testing' => ['testing', true],
            'staging' => ['staging', false],
            'production' => ['production', false],
        ];
    }

    #[DataProvider('environments')]
    public function test_demo_user_is_only_seeded_in_local_and_testing(string $environment, bool $expected): void
    {
        $this->app['env'] = $environment;

        // --force is how the seeder runs on a production server (no confirmation prompt).
        $this->artisan('db:seed', ['--force' => true])->assertSuccessful()->run();

        $this->assertSame($expected, User::query()->where('email', 'test@example.com')->exists());
    }
}

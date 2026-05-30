<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Arr;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Los tests de feature usan requests HTTP directos sin formulario; no
        // necesitan CSRF ya que la autenticación se controla con actingAs() / tokens.
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    /**
     * Override the framework seed helper to force seeding in tests and avoid
     * interactive confirmation prompts when the application environment
     * is switched to 'production' during a test.
     *
     * @param  string|array  $class
     * @return $this
     */
    public function seed($class = 'Database\\Seeders\\DatabaseSeeder')
    {
        foreach (Arr::wrap($class) as $class) {
            $this->artisan('db:seed', [
                '--class' => $class,
                '--force' => true,
                '--no-interaction' => true,
            ]);
        }

        return $this;
    }
}

<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Los tests de feature usan requests HTTP directos sin formulario; no
        // necesitan CSRF ya que la autenticación se controla con actingAs() / tokens.
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }
}

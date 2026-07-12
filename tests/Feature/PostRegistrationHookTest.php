<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use NetCode\Identity\Application\Ports\PostRegistrationHook;
use NetCode\Identity\Application\Ports\RegistrationPayloadFactory;
use NetCode\Identity\Infrastructure\Registration\NoRegistrationPayload;
use NetCode\Identity\Tests\Support\OrganizationPayload;
use NetCode\Identity\Tests\Support\OrganizationPayloadFactory;
use NetCode\Identity\Tests\Support\RecordingPostRegistrationHook;
use NetCode\Identity\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class PostRegistrationHookTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_host_hook_receives_a_typed_payload_built_from_the_request(): void
    {
        $hook = new RecordingPostRegistrationHook;
        $this->app->instance(PostRegistrationHook::class, $hook);
        $this->app->instance(RegistrationPayloadFactory::class, new OrganizationPayloadFactory);

        $this->postJson('/auth/register', [
            'name' => 'Ada',
            'email' => 'ada@example.test',
            'password' => 'password123',
            'organization_name' => 'Acme',
        ])->assertCreated();

        $this->assertInstanceOf(OrganizationPayload::class, $hook->payload);
        $this->assertSame('Acme', $hook->payload->organizationName);
        $this->assertSame('ada@example.test', $hook->user?->email);
    }

    #[Test]
    public function the_default_payload_is_used_when_the_host_provides_no_factory(): void
    {
        $hook = new RecordingPostRegistrationHook;
        $this->app->instance(PostRegistrationHook::class, $hook);

        $this->postJson('/auth/register', [
            'name' => 'Ada',
            'email' => 'ada@example.test',
            'password' => 'password123',
        ])->assertCreated();

        $this->assertInstanceOf(NoRegistrationPayload::class, $hook->payload);
    }
}

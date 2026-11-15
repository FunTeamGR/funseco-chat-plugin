<?php

namespace Funseco\Chat\Tests;

use Funseco\Admin\Tests\Support\AbilitySnapshot;
use Funseco\Core\Testing\TestCase as CoreTestCase;
use Funseco\Messages\MessagesServiceProvider;
use Funseco\Admin\AdminServiceProvider;
use Funseco\Chat\ChatCommandsServiceProvider;

abstract class TestCase extends CoreTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        AbilitySnapshot::apply();
    }

    /** Dependencies first, then this plugin (isolated runs load only these). */
    protected function plugins(): array
    {
        return [
            AdminServiceProvider::class,
            MessagesServiceProvider::class,
            ChatCommandsServiceProvider::class,
        ];
    }
}

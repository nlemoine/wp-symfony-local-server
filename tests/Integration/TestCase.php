<?php

declare(strict_types=1);

namespace n5s\WpSymfonyLocalServer\Tests\Integration;

use Mantle\Testing\Concerns\Hooks;
use Mantle\Testing\Concerns\Interacts_With_Hooks;
use Mantle\Testing\Concerns\Interacts_With_Requests;
use Mantle\Testkit\Integration_Test_Case as MantleTestCase;

abstract class TestCase extends MantleTestCase
{
    use Hooks;
    use Interacts_With_Hooks;
    use Interacts_With_Requests;
}

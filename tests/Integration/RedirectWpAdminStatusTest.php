<?php

declare(strict_types=1);

namespace n5s\WpSymfonyLocalServer\Tests\Integration;

use function n5s\WpSymfonyLocalServer\redirectWpAdminStatus;

class RedirectWpAdminStatusTest extends TestCase
{
    public function testReturns302ForAdminIndexPhpRedirect(): void
    {
        $location = admin_url('index.php');

        $result = redirectWpAdminStatus(301, $location);

        $this->assertSame(302, $result);
    }

    public function testPreservesOriginalStatusForOtherLocations(): void
    {
        $location = admin_url('plugins.php');

        $result = redirectWpAdminStatus(301, $location);

        $this->assertSame(301, $result);
    }

    public function testPreserves302StatusForNonAdminLocations(): void
    {
        $location = home_url('/some-page/');

        $result = redirectWpAdminStatus(302, $location);

        $this->assertSame(302, $result);
    }

    public function testPreserves301StatusForExternalLocations(): void
    {
        $location = 'https://external-site.com/redirect';

        $result = redirectWpAdminStatus(301, $location);

        $this->assertSame(301, $result);
    }

    public function testHandles307StatusCode(): void
    {
        $location = home_url('/temporary-redirect/');

        $result = redirectWpAdminStatus(307, $location);

        $this->assertSame(307, $result);
    }

    public function testHandles308StatusCode(): void
    {
        $location = home_url('/permanent-redirect/');

        $result = redirectWpAdminStatus(308, $location);

        $this->assertSame(308, $result);
    }
}

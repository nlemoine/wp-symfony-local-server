<?php

declare(strict_types=1);

namespace n5s\WpSymfonyLocalServer\Tests\Integration;

use function n5s\WpSymfonyLocalServer\shouldSendThroughProxy;

class ShouldSendThroughProxyTest extends TestCase
{
    public function testReturnsTrueWhenHostsMatch(): void
    {
        $homeUrl = home_url();
        $parsedHome = parse_url($homeUrl);
        $parsedCheck = parse_url($homeUrl . '/some-path/');

        $result = shouldSendThroughProxy(null, $homeUrl . '/some-path/', $parsedCheck, $parsedHome);

        $this->assertTrue($result);
    }

    public function testReturnsOverrideWhenHostsDiffer(): void
    {
        $homeUrl = home_url();
        $parsedHome = parse_url($homeUrl);
        $externalUrl = 'https://external-site.com/api/endpoint';
        $parsedCheck = parse_url($externalUrl);

        $result = shouldSendThroughProxy(null, $externalUrl, $parsedCheck, $parsedHome);

        $this->assertNull($result);
    }

    public function testReturnsOverrideValueWhenHostsDiffer(): void
    {
        $homeUrl = home_url();
        $parsedHome = parse_url($homeUrl);
        $externalUrl = 'https://external-site.com/api/endpoint';
        $parsedCheck = parse_url($externalUrl);

        $result = shouldSendThroughProxy(false, $externalUrl, $parsedCheck, $parsedHome);

        $this->assertFalse($result);
    }

    public function testHandlesMissingHostInCheck(): void
    {
        $homeUrl = home_url();
        $parsedHome = parse_url($homeUrl);
        $parsedCheck = [
            'path' => '/relative/path',
        ];

        $result = shouldSendThroughProxy(null, '/relative/path', $parsedCheck, $parsedHome);

        $this->assertNull($result);
    }

    public function testHandlesMissingHostInHome(): void
    {
        $parsedHome = [
            'path' => '/',
        ];
        $parsedCheck = parse_url('https://example.com/path');

        $result = shouldSendThroughProxy(null, 'https://example.com/path', $parsedCheck, $parsedHome);

        $this->assertNull($result);
    }
}

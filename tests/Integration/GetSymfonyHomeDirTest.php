<?php

declare(strict_types=1);

namespace n5s\WpSymfonyLocalServer\Tests\Integration;

use n5s\WpSymfonyLocalServer\Tests\Integration\Concerns\SymfonyHomeFixture;
use function n5s\WpSymfonyLocalServer\getSymfonyHomeDir;
use function n5s\WpSymfonyLocalServer\getUserConfigDir;

class GetSymfonyHomeDirTest extends TestCase
{
    use SymfonyHomeFixture;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSymfonyHome();
    }

    protected function tearDown(): void
    {
        $this->tearDownSymfonyHome();

        parent::tearDown();
    }

    public function testPrefersLegacyHomeDirWhenPresent(): void
    {
        $legacy = $this->makeLegacyHomeDir();
        $this->makeConfigDir('symfony-cli');

        $this->assertSame($legacy, getSymfonyHomeDir());
    }

    public function testResolvesRenamedConfigDir(): void
    {
        $configDir = $this->makeConfigDir('symfony-cli');

        $this->assertSame($configDir, getSymfonyHomeDir());
    }

    public function testResolvesLegacyConfigDirName(): void
    {
        // Older symfony-cli releases used `symfony5` inside the OS config dir.
        $configDir = $this->makeConfigDir('symfony5');

        $this->assertSame($configDir, getSymfonyHomeDir());
    }

    public function testPrefersRenamedConfigDirOverLegacyName(): void
    {
        $renamed = $this->makeConfigDir('symfony-cli');
        $this->makeConfigDir('symfony5');

        $this->assertSame($renamed, getSymfonyHomeDir());
    }

    public function testDefaultsToRenamedConfigDirWhenNothingExists(): void
    {
        $this->assertSame(getUserConfigDir() . '/symfony-cli', getSymfonyHomeDir());
    }
}

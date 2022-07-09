<?php

namespace Pluguin\Tests\Foundation;

use Exception;
use Pluguin\Contracts\Foundation\Plugin as PluginContract;
use Pluguin\Foundation\Plugin;
use Pluguin\Foundation\ProviderRepository;
use Pluguin\Support\ServiceProvider;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

class FoundationProviderRepositoryTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testProviderRepositoryWorksCorrectly()
    {
        $plugin = m::mock(Plugin::class);

        $manifest = ['eager' => ['foo'], 'deferred' => [], 'when' => []];

        $repo = m::mock(ProviderRepository::class.'[createProvider,loadManifest]', [$plugin, $manifest]);

        $plugin->shouldReceive('register')->once()->with('foo');
        $plugin->shouldReceive('addDeferredServices')->once()->with([]);

        $repo->shouldReceive('loadManifest')->once()->andReturn($manifest);
        $repo->load();
    }
}

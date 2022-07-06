<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Support\DeferrableProvider;
use Pluguin\Foundation\Plugin;
use Illuminate\Foundation\Bootstrap\RegisterFacades;
use Illuminate\Foundation\Events\LocaleUpdated;
use Illuminate\Support\ServiceProvider;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

class FoundationPluginTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testSetLocaleSetsLocaleAndFiresLocaleChangedEvent()
    {
        $plugin = new Plugin;
        $plugin['config'] = $config = m::mock(stdClass::class);
        $config->shouldReceive('set')->once()->with('app.locale', 'foo');
        $plugin['translator'] = $trans = m::mock(stdClass::class);
        $trans->shouldReceive('setLocale')->once()->with('foo');
        $plugin['events'] = $events = m::mock(stdClass::class);
        $events->shouldReceive('dispatch')->once()->with(m::type(LocaleUpdated::class));

        $plugin->setLocale('foo');
    }

    public function testServiceProvidersAreCorrectlyRegistered()
    {
        $provider = m::mock(ApplicationBasicServiceProviderStub::class);
        $class = get_class($provider);
        $provider->shouldReceive('register')->once();
        $plugin = new Plugin;
        $plugin->register($provider);

        $this->assertArrayHasKey($class, $plugin->getLoadedProviders());
    }

    public function testClassesAreBoundWhenServiceProviderIsRegistered()
    {
        $plugin = new Plugin;
        $plugin->register($provider = new class($plugin) extends ServiceProvider
        {
            public $bindings = [
                AbstractClass::class => ConcreteClass::class,
            ];
        });

        $this->assertArrayHasKey(get_class($provider), $plugin->getLoadedProviders());

        $instance = $plugin->make(AbstractClass::class);

        $this->assertInstanceOf(ConcreteClass::class, $instance);
        $this->assertNotSame($instance, $plugin->make(AbstractClass::class));
    }

    public function testSingletonsAreCreatedWhenServiceProviderIsRegistered()
    {
        $plugin = new Plugin;
        $plugin->register($provider = new class($plugin) extends ServiceProvider
        {
            public $singletons = [
                AbstractClass::class => ConcreteClass::class,
            ];
        });

        $this->assertArrayHasKey(get_class($provider), $plugin->getLoadedProviders());

        $instance = $plugin->make(AbstractClass::class);

        $this->assertInstanceOf(ConcreteClass::class, $instance);
        $this->assertSame($instance, $plugin->make(AbstractClass::class));
    }

    public function testServiceProvidersAreCorrectlyRegisteredWhenRegisterMethodIsNotFilled()
    {
        $provider = m::mock(ServiceProvider::class);
        $class = get_class($provider);
        $provider->shouldReceive('register')->once();
        $plugin = new Plugin;
        $plugin->register($provider);

        $this->assertArrayHasKey($class, $plugin->getLoadedProviders());
    }

    public function testServiceProvidersCouldBeLoaded()
    {
        $provider = m::mock(ServiceProvider::class);
        $class = get_class($provider);
        $provider->shouldReceive('register')->once();
        $plugin = new Plugin;
        $plugin->register($provider);

        $this->assertTrue($plugin->providerIsLoaded($class));
        $this->assertFalse($plugin->providerIsLoaded(ApplicationBasicServiceProviderStub::class));
    }

    public function testDeferredServicesMarkedAsBound()
    {
        $plugin = new Plugin;
        $plugin->setDeferredServices(['foo' => ApplicationDeferredServiceProviderStub::class]);
        $this->assertTrue($plugin->bound('foo'));
        $this->assertSame('foo', $plugin->make('foo'));
    }

    public function testDeferredServicesAreSharedProperly()
    {
        $plugin = new Plugin;
        $plugin->setDeferredServices(['foo' => ApplicationDeferredSharedServiceProviderStub::class]);
        $this->assertTrue($plugin->bound('foo'));
        $one = $plugin->make('foo');
        $two = $plugin->make('foo');
        $this->assertInstanceOf(stdClass::class, $one);
        $this->assertInstanceOf(stdClass::class, $two);
        $this->assertSame($one, $two);
    }

    public function testDeferredServicesCanBeExtended()
    {
        $plugin = new Plugin;
        $plugin->setDeferredServices(['foo' => ApplicationDeferredServiceProviderStub::class]);
        $plugin->extend('foo', function ($instance, $container) {
            return $instance.'bar';
        });
        $this->assertSame('foobar', $plugin->make('foo'));
    }

    public function testDeferredServiceProviderIsRegisteredOnlyOnce()
    {
        $plugin = new Plugin;
        $plugin->setDeferredServices(['foo' => ApplicationDeferredServiceProviderCountStub::class]);
        $obj = $plugin->make('foo');
        $this->assertInstanceOf(stdClass::class, $obj);
        $this->assertSame($obj, $plugin->make('foo'));
        $this->assertEquals(1, ApplicationDeferredServiceProviderCountStub::$count);
    }

    public function testDeferredServiceDontRunWhenInstanceSet()
    {
        $plugin = new Plugin;
        $plugin->setDeferredServices(['foo' => ApplicationDeferredServiceProviderStub::class]);
        $plugin->instance('foo', 'bar');
        $instance = $plugin->make('foo');
        $this->assertSame('bar', $instance);
    }

    public function testDeferredServicesAreLazilyInitialized()
    {
        ApplicationDeferredServiceProviderStub::$initialized = false;
        $plugin = new Plugin;
        $plugin->setDeferredServices(['foo' => ApplicationDeferredServiceProviderStub::class]);
        $this->assertTrue($plugin->bound('foo'));
        $this->assertFalse(ApplicationDeferredServiceProviderStub::$initialized);
        $plugin->extend('foo', function ($instance, $container) {
            return $instance.'bar';
        });
        $this->assertFalse(ApplicationDeferredServiceProviderStub::$initialized);
        $this->assertSame('foobar', $plugin->make('foo'));
        $this->assertTrue(ApplicationDeferredServiceProviderStub::$initialized);
    }

    public function testDeferredServicesCanRegisterFactories()
    {
        $plugin = new Plugin;
        $plugin->setDeferredServices(['foo' => ApplicationFactoryProviderStub::class]);
        $this->assertTrue($plugin->bound('foo'));
        $this->assertEquals(1, $plugin->make('foo'));
        $this->assertEquals(2, $plugin->make('foo'));
        $this->assertEquals(3, $plugin->make('foo'));
    }

    public function testSingleProviderCanProvideMultipleDeferredServices()
    {
        $plugin = new Plugin;
        $plugin->setDeferredServices([
            'foo' => ApplicationMultiProviderStub::class,
            'bar' => ApplicationMultiProviderStub::class,
        ]);
        $this->assertSame('foo', $plugin->make('foo'));
        $this->assertSame('foobar', $plugin->make('bar'));
    }

    public function testDeferredServiceIsLoadedWhenAccessingImplementationThroughInterface()
    {
        $plugin = new Plugin;
        $plugin->setDeferredServices([
            SampleInterface::class => InterfaceToImplementationDeferredServiceProvider::class,
            SampleImplementation::class => SampleImplementationDeferredServiceProvider::class,
        ]);
        $instance = $plugin->make(SampleInterface::class);
        $this->assertSame('foo', $instance->getPrimitive());
    }

    public function testEnvironment()
    {
        $plugin = new Plugin;
        $plugin['env'] = 'foo';

        $this->assertSame('foo', $plugin->environment());

        $this->assertTrue($plugin->environment('foo'));
        $this->assertTrue($plugin->environment('f*'));
        $this->assertTrue($plugin->environment('foo', 'bar'));
        $this->assertTrue($plugin->environment(['foo', 'bar']));

        $this->assertFalse($plugin->environment('qux'));
        $this->assertFalse($plugin->environment('q*'));
        $this->assertFalse($plugin->environment('qux', 'bar'));
        $this->assertFalse($plugin->environment(['qux', 'bar']));
    }

    public function testEnvironmentHelpers()
    {
        $local = new Plugin;
        $local['env'] = 'local';

        $this->assertTrue($local->isLocal());
        $this->assertFalse($local->isProduction());
        $this->assertFalse($local->runningUnitTests());

        $production = new Plugin;
        $production['env'] = 'production';

        $this->assertTrue($production->isProduction());
        $this->assertFalse($production->isLocal());
        $this->assertFalse($production->runningUnitTests());

        $testing = new Plugin;
        $testing['env'] = 'testing';

        $this->assertTrue($testing->runningUnitTests());
        $this->assertFalse($testing->isLocal());
        $this->assertFalse($testing->isProduction());
    }

    public function testDebugHelper()
    {
        $debugOff = new Plugin;
        $debugOff['config'] = new Repository(['app' => ['debug' => false]]);

        $this->assertFalse($debugOff->hasDebugModeEnabled());

        $debugOn = new Plugin;
        $debugOn['config'] = new Repository(['app' => ['debug' => true]]);

        $this->assertTrue($debugOn->hasDebugModeEnabled());
    }

    public function testMethodAfterLoadingEnvironmentAddsClosure()
    {
        $plugin = new Plugin;
        $closure = function () {
            //
        };
        $plugin->afterLoadingEnvironment($closure);
        $this->assertArrayHasKey(0, $plugin['events']->getListeners('bootstrapped: Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables'));
    }

    public function testBeforeBootstrappingAddsClosure()
    {
        $plugin = new Plugin;
        $closure = function () {
            //
        };
        $plugin->beforeBootstrapping(RegisterFacades::class, $closure);
        $this->assertArrayHasKey(0, $plugin['events']->getListeners('bootstrapping: Illuminate\Foundation\Bootstrap\RegisterFacades'));
    }

    public function testTerminationTests()
    {
        $plugin = new Plugin;

        $result = [];
        $callback1 = function () use (&$result) {
            $result[] = 1;
        };

        $callback2 = function () use (&$result) {
            $result[] = 2;
        };

        $callback3 = function () use (&$result) {
            $result[] = 3;
        };

        $plugin->terminating($callback1);
        $plugin->terminating($callback2);
        $plugin->terminating($callback3);

        $plugin->terminate();

        $this->assertEquals([1, 2, 3], $result);
    }

    public function testAfterBootstrappingAddsClosure()
    {
        $plugin = new Plugin;
        $closure = function () {
            //
        };
        $plugin->afterBootstrapping(RegisterFacades::class, $closure);
        $this->assertArrayHasKey(0, $plugin['events']->getListeners('bootstrapped: Illuminate\Foundation\Bootstrap\RegisterFacades'));
    }

    public function testTerminationCallbacksCanAcceptAtNotation()
    {
        $plugin = new Plugin;
        $plugin->terminating(ConcreteTerminator::class.'@terminate');

        $plugin->terminate();

        $this->assertEquals(1, ConcreteTerminator::$counter);
    }

    public function testBootingCallbacks()
    {
        $pluginlication = new Plugin;

        $counter = 0;
        $closure = function ($plugin) use (&$counter, $pluginlication) {
            $counter++;
            $this->assertSame($pluginlication, $plugin);
        };

        $closure2 = function ($plugin) use (&$counter, $pluginlication) {
            $counter++;
            $this->assertSame($pluginlication, $plugin);
        };

        $pluginlication->booting($closure);
        $pluginlication->booting($closure2);

        $pluginlication->boot();

        $this->assertEquals(2, $counter);
    }

    public function testBootedCallbacks()
    {
        $pluginlication = new Plugin;

        $counter = 0;
        $closure = function ($plugin) use (&$counter, $pluginlication) {
            $counter++;
            $this->assertSame($pluginlication, $plugin);
        };

        $closure2 = function ($plugin) use (&$counter, $pluginlication) {
            $counter++;
            $this->assertSame($pluginlication, $plugin);
        };

        $closure3 = function ($plugin) use (&$counter, $pluginlication) {
            $counter++;
            $this->assertSame($pluginlication, $plugin);
        };

        $pluginlication->booting($closure);
        $pluginlication->booted($closure);
        $pluginlication->booted($closure2);
        $pluginlication->boot();

        $this->assertEquals(3, $counter);

        $pluginlication->booted($closure3);

        $this->assertEquals(4, $counter);
    }

    public function testGetNamespace()
    {
        $plugin1 = new Plugin(realpath(__DIR__.'/fixtures/laravel1'));
        $plugin2 = new Plugin(realpath(__DIR__.'/fixtures/laravel2'));

        $this->assertSame('Laravel\\One\\', $plugin1->getNamespace());
        $this->assertSame('Laravel\\Two\\', $plugin2->getNamespace());
    }

    public function testCachePathsResolveToBootstrapCacheDirectory()
    {
        $plugin = new Plugin('/base/path');

        $ds = DIRECTORY_SEPARATOR;
        $this->assertSame('/base/path'.$ds.'bootstrap'.$ds.'cache/services.php', $plugin->getCachedServicesPath());
        $this->assertSame('/base/path'.$ds.'bootstrap'.$ds.'cache/packages.php', $plugin->getCachedPackagesPath());
        $this->assertSame('/base/path'.$ds.'bootstrap'.$ds.'cache/config.php', $plugin->getCachedConfigPath());
        $this->assertSame('/base/path'.$ds.'bootstrap'.$ds.'cache/routes-v7.php', $plugin->getCachedRoutesPath());
        $this->assertSame('/base/path'.$ds.'bootstrap'.$ds.'cache/events.php', $plugin->getCachedEventsPath());
    }

    public function testEnvPathsAreUsedForCachePathsWhenSpecified()
    {
        $plugin = new Plugin('/base/path');
        $_SERVER['APP_SERVICES_CACHE'] = '/absolute/path/services.php';
        $_SERVER['APP_PACKAGES_CACHE'] = '/absolute/path/packages.php';
        $_SERVER['APP_CONFIG_CACHE'] = '/absolute/path/config.php';
        $_SERVER['APP_ROUTES_CACHE'] = '/absolute/path/routes.php';
        $_SERVER['APP_EVENTS_CACHE'] = '/absolute/path/events.php';

        $this->assertSame('/absolute/path/services.php', $plugin->getCachedServicesPath());
        $this->assertSame('/absolute/path/packages.php', $plugin->getCachedPackagesPath());
        $this->assertSame('/absolute/path/config.php', $plugin->getCachedConfigPath());
        $this->assertSame('/absolute/path/routes.php', $plugin->getCachedRoutesPath());
        $this->assertSame('/absolute/path/events.php', $plugin->getCachedEventsPath());

        unset(
            $_SERVER['APP_SERVICES_CACHE'],
            $_SERVER['APP_PACKAGES_CACHE'],
            $_SERVER['APP_CONFIG_CACHE'],
            $_SERVER['APP_ROUTES_CACHE'],
            $_SERVER['APP_EVENTS_CACHE']
        );
    }

    public function testEnvPathsAreUsedAndMadeAbsoluteForCachePathsWhenSpecifiedAsRelative()
    {
        $plugin = new Plugin('/base/path');
        $_SERVER['APP_SERVICES_CACHE'] = 'relative/path/services.php';
        $_SERVER['APP_PACKAGES_CACHE'] = 'relative/path/packages.php';
        $_SERVER['APP_CONFIG_CACHE'] = 'relative/path/config.php';
        $_SERVER['APP_ROUTES_CACHE'] = 'relative/path/routes.php';
        $_SERVER['APP_EVENTS_CACHE'] = 'relative/path/events.php';

        $ds = DIRECTORY_SEPARATOR;
        $this->assertSame('/base/path'.$ds.'relative/path/services.php', $plugin->getCachedServicesPath());
        $this->assertSame('/base/path'.$ds.'relative/path/packages.php', $plugin->getCachedPackagesPath());
        $this->assertSame('/base/path'.$ds.'relative/path/config.php', $plugin->getCachedConfigPath());
        $this->assertSame('/base/path'.$ds.'relative/path/routes.php', $plugin->getCachedRoutesPath());
        $this->assertSame('/base/path'.$ds.'relative/path/events.php', $plugin->getCachedEventsPath());

        unset(
            $_SERVER['APP_SERVICES_CACHE'],
            $_SERVER['APP_PACKAGES_CACHE'],
            $_SERVER['APP_CONFIG_CACHE'],
            $_SERVER['APP_ROUTES_CACHE'],
            $_SERVER['APP_EVENTS_CACHE']
        );
    }

    public function testEnvPathsAreUsedAndMadeAbsoluteForCachePathsWhenSpecifiedAsRelativeWithNullBasePath()
    {
        $plugin = new Plugin;
        $_SERVER['APP_SERVICES_CACHE'] = 'relative/path/services.php';
        $_SERVER['APP_PACKAGES_CACHE'] = 'relative/path/packages.php';
        $_SERVER['APP_CONFIG_CACHE'] = 'relative/path/config.php';
        $_SERVER['APP_ROUTES_CACHE'] = 'relative/path/routes.php';
        $_SERVER['APP_EVENTS_CACHE'] = 'relative/path/events.php';

        $ds = DIRECTORY_SEPARATOR;
        $this->assertSame($ds.'relative/path/services.php', $plugin->getCachedServicesPath());
        $this->assertSame($ds.'relative/path/packages.php', $plugin->getCachedPackagesPath());
        $this->assertSame($ds.'relative/path/config.php', $plugin->getCachedConfigPath());
        $this->assertSame($ds.'relative/path/routes.php', $plugin->getCachedRoutesPath());
        $this->assertSame($ds.'relative/path/events.php', $plugin->getCachedEventsPath());

        unset(
            $_SERVER['APP_SERVICES_CACHE'],
            $_SERVER['APP_PACKAGES_CACHE'],
            $_SERVER['APP_CONFIG_CACHE'],
            $_SERVER['APP_ROUTES_CACHE'],
            $_SERVER['APP_EVENTS_CACHE']
        );
    }

    public function testEnvPathsAreAbsoluteInWindows()
    {
        $plugin = new Plugin(__DIR__);
        $plugin->addAbsoluteCachePathPrefix('C:');
        $_SERVER['APP_SERVICES_CACHE'] = 'C:\framework\services.php';
        $_SERVER['APP_PACKAGES_CACHE'] = 'C:\framework\packages.php';
        $_SERVER['APP_CONFIG_CACHE'] = 'C:\framework\config.php';
        $_SERVER['APP_ROUTES_CACHE'] = 'C:\framework\routes.php';
        $_SERVER['APP_EVENTS_CACHE'] = 'C:\framework\events.php';

        $this->assertSame('C:\framework\services.php', $plugin->getCachedServicesPath());
        $this->assertSame('C:\framework\packages.php', $plugin->getCachedPackagesPath());
        $this->assertSame('C:\framework\config.php', $plugin->getCachedConfigPath());
        $this->assertSame('C:\framework\routes.php', $plugin->getCachedRoutesPath());
        $this->assertSame('C:\framework\events.php', $plugin->getCachedEventsPath());

        unset(
            $_SERVER['APP_SERVICES_CACHE'],
            $_SERVER['APP_PACKAGES_CACHE'],
            $_SERVER['APP_CONFIG_CACHE'],
            $_SERVER['APP_ROUTES_CACHE'],
            $_SERVER['APP_EVENTS_CACHE']
        );
    }
}

class ApplicationBasicServiceProviderStub extends ServiceProvider
{
    public function boot()
    {
        //
    }

    public function register()
    {
        //
    }
}

class ApplicationDeferredSharedServiceProviderStub extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton('foo', function () {
            return new stdClass;
        });
    }
}

class ApplicationDeferredServiceProviderCountStub extends ServiceProvider implements DeferrableProvider
{
    public static $count = 0;

    public function register()
    {
        static::$count++;
        $this->app['foo'] = new stdClass;
    }
}

class ApplicationDeferredServiceProviderStub extends ServiceProvider implements DeferrableProvider
{
    public static $initialized = false;

    public function register()
    {
        static::$initialized = true;
        $this->app['foo'] = 'foo';
    }
}

interface SampleInterface
{
    public function getPrimitive();
}

class SampleImplementation implements SampleInterface
{
    private $primitive;

    public function __construct($primitive)
    {
        $this->primitive = $primitive;
    }

    public function getPrimitive()
    {
        return $this->primitive;
    }
}

class InterfaceToImplementationDeferredServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->bind(SampleInterface::class, SampleImplementation::class);
    }
}

class SampleImplementationDeferredServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->when(SampleImplementation::class)->needs('$primitive')->give(function () {
            return 'foo';
        });
    }
}

class ApplicationFactoryProviderStub extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->bind('foo', function () {
            static $count = 0;

            return ++$count;
        });
    }
}

class ApplicationMultiProviderStub extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton('foo', function () {
            return 'foo';
        });
        $this->app->singleton('bar', function ($plugin) {
            return $plugin['foo'].'bar';
        });
    }
}

abstract class AbstractClass
{
    //
}

class ConcreteClass extends AbstractClass
{
    //
}

class ConcreteTerminator
{
    public static $counter = 0;

    public function terminate()
    {
        return self::$counter++;
    }
}
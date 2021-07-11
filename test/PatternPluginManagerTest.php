<?php

namespace LaminasTest\Cache;

use Laminas\Cache\Exception\RuntimeException;
use Laminas\Cache\Pattern\PatternInterface;
use Laminas\Cache\PatternPluginManager;
use Laminas\Cache\Pattern;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\ServiceManager\ServiceManager;
use Laminas\ServiceManager\Test\CommonPluginManagerTrait;
use LaminasTest\Cache\Pattern\TestAsset\TestCachePattern;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

class PatternPluginManagerTest extends TestCase
{
    use CommonPluginManagerTrait;
    use ProphecyTrait;

    protected function getPluginManager()
    {
        return new PatternPluginManager(new ServiceManager());
    }

    protected function getV2InvalidPluginException()
    {
        return RuntimeException::class;
    }

    protected function getInstanceOf()
    {
        return PatternInterface::class;
    }

    public function testGetWillInjectProvidedOptionsAsPatternOptionsInstance()
    {
        $plugins = $this->getPluginManager();
        $storage = $this->prophesize(StorageInterface::class)->reveal();
        $plugin = $plugins->get('callback', [
            'cache_output' => false,
            'storage' => $storage,
        ]);
        $options = $plugin->getOptions();
        $this->assertFalse($options->getCacheOutput());
        $this->assertSame($storage, $options->getStorage());
    }

    public function testBuildWillInjectProvidedOptionsAsPatternOptionsInstance()
    {
        $plugins = $this->getPluginManager();

        if (! method_exists($plugins, 'configure')) {
            $this->markTestSkipped('Test is only relevant for laminas-servicemanager v3');
        }

        $storage = $this->prophesize(StorageInterface::class)->reveal();
        $plugin = $plugins->build('callback', [
            'cache_output' => false,
            'storage' => $storage,
        ]);
        $options = $plugin->getOptions();
        $this->assertFalse($options->getCacheOutput());
        $this->assertSame($storage, $options->getStorage());
    }

    public function testHasPatternCacheFactoriesConfigured(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $instance = new class($container) extends PatternPluginManager {
            public function getFactories(): array
            {
                return $this->factories;
            }
        };

        self::assertEquals([
            Pattern\CallbackCache::class    => Pattern\StoragePatternCacheFactory::class,
            Pattern\CaptureCache::class     => Pattern\PatternCacheFactory::class,
            Pattern\ClassCache::class       => Pattern\StoragePatternCacheFactory::class,
            Pattern\ObjectCache::class      => Pattern\StoragePatternCacheFactory::class,
            Pattern\OutputCache::class      => Pattern\StoragePatternCacheFactory::class,

            // v2 normalized FQCNs
            'laminascachepatterncallbackcache' => Pattern\StoragePatternCacheFactory::class,
            'laminascachepatterncapturecache'  => Pattern\PatternCacheFactory::class,
            'laminascachepatternclasscache'    => Pattern\StoragePatternCacheFactory::class,
            'laminascachepatternobjectcache'   => Pattern\StoragePatternCacheFactory::class,
            'laminascachepatternoutputcache'   => Pattern\StoragePatternCacheFactory::class,
        ], $instance->getFactories());
    }

    public function testWillPassOptionsToCachePattern(): void
    {
        $patternPluginManager = $this->getPluginManager();
        $patternPluginManager->setInvokableClass(TestCachePattern::class);
        $options = ['cache_output' => false];

        $instance = $patternPluginManager->build(TestCachePattern::class, $options);
        self::assertInstanceOf(TestCachePattern::class, $instance);
        self::assertFalse($instance->getOptions()->getCacheOutput());
    }
}

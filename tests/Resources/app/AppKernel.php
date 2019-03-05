<?php

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

class AppKernel extends Kernel
{
    use MicroKernelTrait;

    /**
     * @var string
     */
    private static $cacheDir;

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        $bundles =  [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\TwigBundle\TwigBundle(),
            new \Http\HttplugBundle\HttplugBundle(),
        ];

        if (in_array($this->getEnvironment(), array('dev', 'test', 'psr18'))) {
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
        }

        return $bundles;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
        if ($this->isDebug()) {
            $loader->load(__DIR__.'/config/config_debug.yml');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        $routes->import('@WebProfilerBundle/Resources/config/routing/wdt.xml', '/_wdt');
        $routes->import('@WebProfilerBundle/Resources/config/routing/profiler.xml', '/_profiler');

        if (Kernel::MAJOR_VERSION < 4 || (Kernel::MAJOR_VERSION === 4 && Kernel::MINOR_VERSION === 0)) {
            $routes->add('/', 'kernel:indexAction');
        } else {
            // If 4.1+
            $routes->add('/', 'kernel::indexAction');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir()
    {
        if (null === self::$cacheDir) {
            self::$cacheDir = uniqid('cache');
        }
        return sys_get_temp_dir().'/httplug-bundle/'.self::$cacheDir;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir()
    {
        return sys_get_temp_dir().'/httplug-bundle/logs';
    }

    /**
     * {@inheritdoc}
     */
    protected function getContainerBaseClass()
    {
        return '\PSS\SymfonyMockerContainer\DependencyInjection\MockerContainer';
    }

    public function indexAction()
    {
        return new Response();
    }

    protected function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new PublicServicesForFunctionalTestsPass());
    }
}

class PublicServicesForFunctionalTestsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $services = [
            'httplug.strategy',
            'httplug.auto_discovery.auto_discovered_client',
            'httplug.auto_discovery.auto_discovered_async',
            'httplug.message_factory.default',
            'httplug.stream_factory.default',
            'httplug.uri_factory.default',
            'httplug.async_client.default',
            'httplug.client.default',
            'app.http.plugin.custom',
            'httplug.client.acme',
        ];
        foreach ($services as $service) {
            if ($container->hasDefinition($service)) {
                $container->getDefinition($service)->setPublic(true);
            }

        }

        $aliases = [
            'httplug.client',
        ];
        foreach ($aliases as $alias) {
            if ($container->hasAlias($alias)) {
                $container->getAlias($alias)->setPublic(true);
            }
        }
    }
}

<?php

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
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

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
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
        $routes->add('/', 'kernel:indexAction');
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
}

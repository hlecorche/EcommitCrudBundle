<?php

/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\Tests\App;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle;
use Ecommit\CrudBundle\EcommitCrudBundle;
use Ecommit\JavascriptBundle\EcommitJavascriptBundle;
use Ecommit\UtilBundle\EcommitUtilBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $loader->load($this->getProjectDir().'/config/framework.yaml');
        $loader->load($this->getProjectDir().'/config/doctrine.yaml');
        $loader->load($this->getProjectDir().'/config/security.yaml');
        $loader->load($this->getProjectDir().'/config/services.yaml');
    }

    public function registerBundles()
    {
        return [
            new TwigBundle(),
            new DoctrineBundle(),
            new DoctrineFixturesBundle(),
            new FrameworkBundle(),
            new SecurityBundle(),
            new EcommitCrudBundle(),
            new EcommitJavascriptBundle(),
            new EcommitUtilBundle(),
        ];
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $routes->import($this->getProjectDir().'/config/routes.yaml');
    }

    public function getProjectDir()
    {
        return __DIR__;
    }
}
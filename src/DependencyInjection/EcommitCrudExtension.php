<?php

declare(strict_types=1);

/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\DependencyInjection;

use Ecommit\CrudBundle\Form\Filter\FilterInterface;
use Ecommit\CrudBundle\Form\Searcher\SearcherInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class EcommitCrudExtension extends Extension
{
    /**
     * Loads a specific configuration.
     *
     * @param array            $configs   An array of configuration values
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @throws \InvalidArgumentException When provided tag is not defined in this extension
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $configs = $this->processConfiguration($configuration, $configs);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('crud.php');
        $loader->load('filters.php');

        $container->setParameter('ecommit_crud.theme', $configs['theme']);
        $container->setParameter('ecommit_crud.icon_theme', $configs['icon_theme']);
        $container->setParameter('ecommit_crud.twig_functions_configuration', $configs['twig_functions_configuration']);

        $container->registerForAutoconfiguration(FilterInterface::class)->addTag('ecommit_crud.filter');

        // Searchers are stored in the session: they are data objects, not services
        $container->registerForAutoconfiguration(SearcherInterface::class)->addTag('container.excluded', [
            'source' => 'because searchers are stored in the session and must not be services (to use a service in a searcher, pass it in the search form options)',
        ]);
    }
}

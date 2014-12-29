<?php

/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ecommit_crud');

        $rootNode
            ->children()
                ->arrayNode('images')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('th_image_up')->defaultValue('/bundles/ecommitcrud/images/i16/sort_incr.png')->end()
                        ->scalarNode('th_image_down')->defaultValue('/bundles/ecommitcrud/images/i16/sort_decrease.png')->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}


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

use Ecommit\CrudBundle\Crud\Crud;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('ecommit_crud');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('theme')->isRequired()->end()
                ->arrayNode('template_configuration')
                    ->treatNullLike([])
                    ->prototype('variable')
                    ->end()
                    ->validate()
                        ->ifTrue(function ($v) {
                            foreach ($v as $functionName => $value) {
                                if (!Crud::validateConfigureTemplateFunctionName($functionName)) {
                                    return true;
                                }
                            }
                        })
                        ->thenInvalid('Function name in template_configuration is invalid.')
                    ->end()
                ->end()
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

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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Doctrine\Persistence\ManagerRegistry;
use Ecommit\CrudBundle\Crud\CrudFactory;
use Ecommit\CrudBundle\Crud\CrudResponseGenerator;
use Ecommit\CrudBundle\EventListener\MappingEntities;
use Ecommit\CrudBundle\Form\Type\EntityAjaxType;
use Ecommit\CrudBundle\Twig\CrudExtension;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

return static function (ContainerConfigurator $container): void {
    $container->services()

        ->set('ecommit_crud.locator', ServiceLocator::class)
        ->args([[
            'router' => service(RouterInterface::class),
            'form.factory' => service(FormFactoryInterface::class),
            'request_stack' => service(RequestStack::class),
            'doctrine' => service(ManagerRegistry::class),
            'security.token_storage' => service(TokenStorageInterface::class),
            'ecommit_crud.filters' => service('ecommit_crud.filters'),
        ]])
        ->tag('container.service_locator')

        ->set('ecommit_crud.factory', CrudFactory::class)
        ->args([service('ecommit_crud.locator')])
        ->alias(CrudFactory::class, 'ecommit_crud.factory')

        ->set('ecommit_crud.response_generatror', CrudResponseGenerator::class)
        ->tag('container.service_subscriber')
        ->alias(CrudResponseGenerator::class, 'ecommit_crud.response_generatror')

        ->set('ecommit_crud.twig.crud_extension', CrudExtension::class)
        ->args([
            service('twig.form.renderer'),
            param('ecommit_crud.theme'),
            param('ecommit_crud.icon_theme'),
            param('ecommit_crud.twig_functions_configuration'),
        ])
        ->tag('twig.extension')

        ->set('ecommit_crud.event_listener.mapping_entities', MappingEntities::class)
        ->tag('doctrine.event_listener', ['event' => 'loadClassMetadata'])

        ->set('ecommit_crud.type.entity_ajax', EntityAjaxType::class)
        ->args([
            service(ManagerRegistry::class),
            service(RouterInterface::class),
        ])
        ->tag('form.type')
    ;
};

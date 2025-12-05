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

use Ecommit\CrudBundle\Form\Filter;

return static function (ContainerConfigurator $container): void {
    $container->services()

        ->set('ecommit_crud.filter.boolean', Filter\BooleanFilter::class)
        ->tag('ecommit_crud.filter')

        ->set('ecommit_crud.filter.choice', Filter\ChoiceFilter::class)
        ->tag('ecommit_crud.filter')

        ->set('ecommit_crud.filter.date', Filter\DateFilter::class)
        ->tag('ecommit_crud.filter')

        ->set('ecommit_crud.filter.entity_ajax', Filter\EntityAjaxFilter::class)
        ->tag('ecommit_crud.filter')

        ->set('ecommit_crud.filter.entity', Filter\EntityFilter::class)
        ->tag('ecommit_crud.filter')

        ->set('ecommit_crud.filter.integer', Filter\IntegerFilter::class)
        ->tag('ecommit_crud.filter')

        ->set('ecommit_crud.filter.not_null', Filter\NotNullFilter::class)
        ->tag('ecommit_crud.filter')

        ->set('ecommit_crud.filter.null', Filter\NullFilter::class)
        ->tag('ecommit_crud.filter')

        ->set('ecommit_crud.filter.number', Filter\NumberFilter::class)
        ->tag('ecommit_crud.filter')

        ->set('ecommit_crud.filter.text', Filter\TextFilter::class)
        ->tag('ecommit_crud.filter')
    ;
};

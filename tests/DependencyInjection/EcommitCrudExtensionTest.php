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

namespace Ecommit\CrudBundle\Tests\DependencyInjection;

use Ecommit\CrudBundle\DependencyInjection\EcommitCrudExtension;
use Ecommit\CrudBundle\Tests\Functional\App\Form\Filter\MyFilter;
use Ecommit\CrudBundle\Tests\Functional\App\Form\Searcher\UserSearcher;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Compiler\ResolveInstanceofConditionalsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class EcommitCrudExtensionTest extends KernelTestCase
{
    protected function setUp(): void
    {
        static::bootKernel();
    }

    public function testAutoconfigureTag(): void
    {
        $crudFilters = self::getContainer()->get('ecommit_crud.filters');

        $this->assertTrue($crudFilters->has(MyFilter::class));
    }

    public function testSearcherIsExcludedFromContainer(): void
    {
        $container = new ContainerBuilder();
        $extension = new EcommitCrudExtension();
        $extension->load([[
            'theme' => '@EcommitCrud/Theme/base.html.twig',
            'icon_theme' => '@EcommitCrud/IconTheme/base.html.twig',
        ]], $container);
        $container->register(UserSearcher::class, UserSearcher::class)->setAutoconfigured(true);
        (new ResolveInstanceofConditionalsPass())->process($container);

        $this->assertTrue($container->getDefinition(UserSearcher::class)->hasTag('container.excluded'));
    }
}

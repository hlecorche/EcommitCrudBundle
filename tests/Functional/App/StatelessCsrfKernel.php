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

namespace Ecommit\CrudBundle\Tests\Functional\App;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class StatelessCsrfKernel extends Kernel
{
    public const COOKIE_NAME = 'stateless-csrf-token';

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        parent::configureContainer($container, $loader);

        $container->loadFromExtension('framework', [
            'csrf_protection' => [
                'stateless_token_ids' => ['submit'],
                'cookie_name' => self::COOKIE_NAME,
            ],
            'form' => [
                'csrf_protection' => [
                    'token_id' => 'submit',
                ],
            ],
        ]);
    }

    public function getCacheDir(): string
    {
        return parent::getCacheDir().'/stateless_csrf';
    }
}

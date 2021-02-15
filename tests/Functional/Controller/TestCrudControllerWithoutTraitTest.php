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

namespace Ecommit\CrudBundle\Tests\Functional\Controller;

class TestCrudControllerWithoutTraitTest extends TestCrudControllerTest
{
    public const URL = '/user-without-trait/';
    public const SESSION_NAME = 'user_without_trait';
}

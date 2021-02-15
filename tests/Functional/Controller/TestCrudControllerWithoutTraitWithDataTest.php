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

class TestCrudControllerWithoutTraitWithDataTest extends TestCrudControllerTest
{
    public const URL = '/user-without-trait?test-before-and-after-build-query=1';
    public const SESSION_NAME = 'user_without_trait_with_data';
    public const SEARCH_IN_LIST = 'BEFORE AFTER';
}

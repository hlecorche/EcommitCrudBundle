<?php

/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

abstract class AbstractCrudController extends Controller
{
    use CrudControllerTrait;
}

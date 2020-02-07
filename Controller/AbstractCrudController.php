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

use Symfony\Bundle\FrameworkBundle\Controller\ControllerTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

abstract class AbstractCrudController implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    use ControllerTrait;
    use CrudControllerTrait;

    /**
     * Gets a container configuration parameter by its name.
     *
     * @return mixed
     *
     * @final
     */
    protected function getParameter(string $name)
    {
        return $this->container->getParameter($name);
    }
}

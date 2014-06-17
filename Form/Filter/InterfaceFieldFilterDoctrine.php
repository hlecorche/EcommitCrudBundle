<?php

/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\Form\Filter;

use Doctrine\Common\Persistence\ManagerRegistry;

interface InterfaceFieldFilterDoctrine
{
    public function getRegistry();
    public function setRegistry(ManagerRegistry $registry);
}
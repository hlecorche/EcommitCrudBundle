<?php

/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\DoctrineExtension;

use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;

class Paginate
{
    /**
     * Returns total results (SQL function "count")
     * 
     * @param Query $query
     * @return int 
     */
    static public function count(Query $query)
    {
        $doctrine_paginator = new Paginator($query, false);
        return $doctrine_paginator->count();
    }
}
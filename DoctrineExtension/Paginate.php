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
        $countQuery = clone $query;

        //Whene clones Query object, parameters are deleted
        $countQuery->setParameters($query->getParameters());
        
        $countQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, array('Ecommit\CrudBundle\DoctrineExtension\CountSqlWalker'));
        $countQuery->setFirstResult(null)->setMaxResults(null);

        return $countQuery->getSingleScalarResult();
    }
}
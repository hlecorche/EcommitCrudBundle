<?php

/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\Paginator;

use Doctrine\DBAL\Query\QueryBuilder;
use Ecommit\CrudBundle\Paginator\DoctrinePaginator;

class DbalPaginator extends DoctrinePaginator
{
    /**
     * {@inheritDoc}
     */
    public function init()
    {
        //Calculation of the number of lines
        if (is_null($this->totalResults)) {
            $queryBuilderCount = clone $this->query;
            $queryBuilderClone = clone $this->query;

            $queryBuilderClone->resetQueryPart('orderBy'); //Disable sort (> performance)

            $queryBuilderCount->resetQueryParts(); //Remove Query Parts
            $queryBuilderCount->select('count(*)')
                ->from('(' . $queryBuilderClone->getSql() . ')', 'mainquery');

            $count = $queryBuilderCount->execute()->fetchColumn(0);
            $this->setCountResults($count);
        } else {
            $this->setCountResults($this->totalResults);
        }

        $this->initQuery();
    }

    /**
     * Sets the QueryBuilder
     *
     * @param QueryBuilder $query
     */
    public function setDbalQueryBuilder(QueryBuilder $query)
    {
        $this->query = $query;
    }

    /**
     * {@inheritDoc}
     */
    public function getResults()
    {
        return $this->query->execute()->fetchAll();
    }

    /**
     * {@inheritDoc}
     */
    protected function retrieveObject($offset)
    {
        $query_retrieve = clone $this->query;
        $query_retrieve->setFirstResult($offset - 1);
        $query_retrieve->setMaxResults(1);
        $results = $query_retrieve->execute()->fetchAll();

        return $results[0];
    }
}

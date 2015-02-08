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

class DbalPaginator extends DoctrinePaginator
{
    /**
     * {@inheritDoc}
     */
    public function init()
    {
        //Calculation of the number of lines
        if (is_null($this->manualCountResults)) {
            $queryBuilderCount = clone $this->query;
            $queryBuilderClone = clone $this->query;

            $queryBuilderClone->resetQueryPart('orderBy'); //Disable sort (> performance)

            $queryBuilderCount->resetQueryParts(); //Remove Query Parts
            $queryBuilderCount->select('count(*)')
                ->from('(' . $queryBuilderClone->getSql() . ')', 'mainquery');

            $count = $queryBuilderCount->execute()->fetchColumn(0);
            $this->setCountResults($count);
        } else {
            $this->setCountResults($this->manualCountResults);
        }

        $this->initQuery();
    }

    /**
     * Sets the QueryBuilder
     *
     * @param QueryBuilder $query
     * @return DbalPaginator
     */
    public function setDbalQueryBuilder(QueryBuilder $query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getResults()
    {
        if (is_null($this->results)) {
            $results = $this->query->execute()->fetchAll();
            $this->results = new \ArrayIterator($results);
        }

        return $this->results;
    }
}

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

class DoctrineDBALPaginator extends AbstractDoctrinePaginator
{
    /**
     * {@inheritDoc}
     */
    protected function getQueryBuilderClass()
    {
        return 'Doctrine\DBAL\Query\QueryBuilder';
    }

    /**
     * {@inheritDoc}
     */
    public function initPaginator()
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
    }

    /**
     * Sets the QueryBuilder
     *
     * @param mixed $query
     * @return DoctrineDBALPaginator
     * @deprecated Deprecated since version 2.2. Use setQueryBuilder method instead.
     */
    public function setDbalQueryBuilder($query)
    {
        trigger_error('setDbalQueryBuilder is deprecated since 2.2 version. Use setQueryBuilder instead', E_USER_DEPRECATED);

        return $this->setQueryBuilder($query);
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
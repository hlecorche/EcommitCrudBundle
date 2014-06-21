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

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Ecommit\CrudBundle\DoctrineExtension\Paginate;

class DoctrinePaginator extends AbstractPaginator
{
    protected $query = null;
    protected $manualCountResults = null;

    /**
     * {@inheritDoc}
     */
    public function init()
    {
        //Calculation of the number of lines
        if (is_null($this->manualCountResults)) {
            $count = Paginate::count($this->query->getQuery());
            $this->setCountResults($count);
        } else {
            $this->setCountResults($this->manualCountResults);
        }
        $this->initQuery();
    }

    protected function initQuery()
    {
        $this->query->setFirstResult(0);
        $this->query->setMaxResults(0);
        if ($this->getPage() == 0 || $this->getMaxPerPage() == 0 || $this->getCountResults() == 0) {
            $this->setLastPage(0);
        } else {
            $this->setLastPage(\ceil($this->getCountResults() / $this->getMaxPerPage()));
            $offset = ($this->getPage() - 1) * $this->getMaxPerPage();

            $this->query->setFirstResult($offset);
            $this->query->setMaxResults($this->getMaxPerPage());
        }
    }


    /**
     * Returns QueryBuilder
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->query;
    }

    /**
     * Sets the QueryBuilder
     *
     * @param QueryBuilder $query
     */
    public function setQueryBuilder(QueryBuilder $query)
    {
        $this->query = $query;
    }

    /**
     * Returns manual total results
     *
     * @return Int
     */
    public function getManualCountResults()
    {
        return $this->manualCountResults;
    }

    /**
     * Sets manual total results
     *
     * @param Int $manualCountResults
     */
    public function setManualCountResults($manualCountResults)
    {
        $this->manualCountResults = $manualCountResults;
    }

    /**
     * {@inheritDoc}
     */
    public function getResults($hydrationMode = Query::HYDRATE_OBJECT)
    {
        $query = $this->query->getQuery();

        return $query->execute(array(), $hydrationMode);
    }

    /**
     * {@inheritDoc}
     */
    protected function retrieveObject($offset)
    {
        $queryRetrieve = clone $this->query;
        $queryRetrieve->setFirstResult($offset - 1);
        $queryRetrieve->setMaxResults(1);
        $results = $queryRetrieve->getQuery()->execute();

        return $results[0];
    }
}
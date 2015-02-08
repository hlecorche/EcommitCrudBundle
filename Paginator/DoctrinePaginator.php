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
use Doctrine\ORM\Tools\Pagination\Paginator;
use Ecommit\CrudBundle\DoctrineExtension\Paginate;

class DoctrinePaginator extends AbstractPaginator
{
    protected $query = null;
    protected $manualCountResults = null;
    protected $simplifiedRequest = true;
    protected $fetchJoinCollection = false;

    /**
     * {@inheritDoc}
     */
    public function init()
    {
        //Calculation of the number of lines
        if (is_null($this->manualCountResults)) {
            $count = Paginate::count($this->query->getQuery(), $this->simplifiedRequest);
            $this->setCountResults($count);
        } else {
            $this->setCountResults($this->manualCountResults);
        }
        $this->initQuery();
    }

    protected function initQuery()
    {
        $this->initLastPage();
        $this->query->setFirstResult(0);
        $this->query->setMaxResults(0);

        if ($this->getCountResults() > 0) {
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
     * @return DoctrinePaginator
     */
    public function setQueryBuilder(QueryBuilder $query)
    {
        $this->query = $query;

        return $this;
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
     * @return DoctrinePaginator
     */
    public function setManualCountResults($manualCountResults)
    {
        $this->manualCountResults = $manualCountResults;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isSimplifiedRequest()
    {
        return $this->simplifiedRequest;
    }

    /**
     * Use simplified request (not subrequest and not order by) or not when count results
     * @param boolean $simplifiedRequest
     * @return DoctrinePaginator
     */
    public function setSimplifiedRequest($simplifiedRequest)
    {
        $this->simplifiedRequest = $simplifiedRequest;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isFetchJoinCollection()
    {
        return $this->fetchJoinCollection;
    }

    /**
     * Set to true when fetch join a to-many collection
     * In that case 3 instead of the 2 queries described are executed
     * @param boolean $fetchJoinCollection
     * @return DoctrinePaginator
     */
    public function setFetchJoinCollection($fetchJoinCollection)
    {
        $this->fetchJoinCollection = $fetchJoinCollection;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getResults()
    {
        if (is_null($this->results)) {
            $query = $this->query->getQuery();
            $paginator = new Paginator($query, $this->fetchJoinCollection);
            $paginator->setUseOutputWalkers(!$this->simplifiedRequest);
            $this->results = $paginator->getIterator();
        }

        return $this->results;
    }
}

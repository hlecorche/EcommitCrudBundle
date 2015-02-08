<?php
/**
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\Paginator;

abstract class AbstractDoctrinePaginator extends AbstractPaginator
{
    protected $query = null;
    protected $manualCountResults = null;

    /**
     * @return string
     */
    abstract protected function getQueryBuilderClass();

    abstract protected function initPaginator();

    public function init()
    {
        if (is_null($this->query)) {
            throw new \Exception('QueryBuilder must be defined.');
        }

        $this->initPaginator();

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
     * @return mixed
     */
    public function getQueryBuilder()
    {
        return $this->query;
    }

    /**
     * Sets the QueryBuilder
     *
     * @param mixed $query
     * @return AbstractDoctrinePaginator
     */
    public function setQueryBuilder($query)
    {
        $queryBuilderClass = $this->getQueryBuilderClass();
        if (!($query instanceof $queryBuilderClass)) {
            throw new \Exception('QueryBuilder must be an instance of '.$queryBuilderClass);
        }
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
     * @return AbstractDoctrinePaginator
     */
    public function setManualCountResults($manualCountResults)
    {
        $this->manualCountResults = $manualCountResults;

        return $this;
    }
}

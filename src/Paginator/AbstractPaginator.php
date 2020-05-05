<?php

declare(strict_types=1);

/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\Paginator;

abstract class AbstractPaginator implements \IteratorAggregate, \Countable
{
    protected $page = 1;
    protected $maxPerPage = 1;
    protected $lastPage = 1;
    protected $countResults = 0;
    protected $results = null;

    /**
     * Constructor.
     *
     * @param string $class      The model class
     * @param int    $maxPerPage Number of records to display per page
     *
     * @return AbstractPaginator
     */
    public function __construct($maxPerPage = 10)
    {
        $this->setMaxPerPage($maxPerPage);

        return $this;
    }

    /**
     * Initializes the pager.
     */
    abstract public function init();

    /**
     * Returns an array of results on the given page.
     *
     * @return \ArrayIterator
     */
    abstract public function getResults();

    /**
     * Returns true if the current query requires pagination.
     *
     * @return bool
     */
    public function haveToPaginate()
    {
        return $this->getCountResults() > $this->getMaxPerPage();
    }

    /**
     * Returns the first index on the current page.
     *
     * @return int
     */
    public function getFirstIndice()
    {
        if (0 == $this->getCountResults()) {
            return 0;
        }

        return ($this->page - 1) * $this->maxPerPage + 1;
    }

    /**
     * Returns the last index on the current page.
     *
     * @return int
     */
    public function getLastIndice()
    {
        if ($this->page * $this->maxPerPage >= $this->countResults) {
            return $this->countResults;
        }

        return $this->page * $this->maxPerPage;
    }

    /**
     * Returns the number of results.
     *
     * @return int
     */
    public function getCountResults()
    {
        return $this->countResults;
    }

    /**
     * Sets the number of results.
     *
     * @param int $nb
     */
    protected function setCountResults($nb): void
    {
        $this->countResults = $nb;
    }

    /**
     * Returns the first page number.
     *
     * @return int
     */
    public function getFirstPage()
    {
        return 1;
    }

    /**
     * Returns the last page number.
     *
     * @return int
     */
    public function getLastPage()
    {
        return $this->lastPage;
    }

    /**
     * Init the last page number.
     */
    protected function initLastPage(): void
    {
        if ($this->getCountResults() > 0) {
            $lastPage = (int) ceil($this->getCountResults() / $this->getMaxPerPage());
        } else {
            $lastPage = 1;
        }
        $this->lastPage = $lastPage;

        if ($this->getPage() > $lastPage) {
            $this->setPage($lastPage);
        }
    }

    /**
     * Returns the current page.
     *
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Returns the next page.
     *
     * @return int
     */
    public function getNextPage()
    {
        return min($this->getPage() + 1, $this->getLastPage());
    }

    /**
     * Returns the previous page.
     *
     * @return int
     */
    public function getPreviousPage()
    {
        return max($this->getPage() - 1, $this->getFirstPage());
    }

    /**
     * Sets the current page.
     *
     * @param int $page
     *
     * @return AbstractPaginator
     */
    public function setPage($page)
    {
        $this->page = (int) $page;

        if ($this->page <= 0) {
            $this->page = 1;
        }

        return $this;
    }

    /**
     * Returns the maximum number of results per page.
     *
     * @return int
     */
    public function getMaxPerPage()
    {
        return $this->maxPerPage;
    }

    /**
     * Sets the maximum number of results per page.
     *
     * @param int $max
     *
     * @return AbstractPaginator
     */
    public function setMaxPerPage($max)
    {
        $max = (int) $max;

        if ($max <= 0) {
            throw new \Exception('Max results value must be positive');
        }
        $this->maxPerPage = $max;

        return $this;
    }

    /**
     * Returns true if on the first page.
     *
     * @return bool
     */
    public function isFirstPage()
    {
        return 1 == $this->page;
    }

    /**
     * Returns true if on the last page.
     *
     * @return bool
     */
    public function isLastPage()
    {
        return $this->page == $this->lastPage;
    }

    /**
     * Returns true if the properties used for iteration have been initialized.
     *
     * @return bool
     */
    protected function isIteratorInitialized()
    {
        return null !== $this->results;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->getCountResults();
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return $this->getResults();
    }
}

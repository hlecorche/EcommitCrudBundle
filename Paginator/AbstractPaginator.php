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
     * @param string $class The model class
     * @param integer $maxPerPage Number of records to display per page
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
     * @return boolean
     */
    public function haveToPaginate()
    {
        return $this->getCountResults() > $this->getMaxPerPage();
    }

    /**
     * Returns the first index on the current page.
     *
     * @return integer
     */
    public function getFirstIndice()
    {
        if ($this->getCountResults() == 0) {
            return 0;
        }

        return ($this->page - 1) * $this->maxPerPage + 1;
    }

    /**
     * Returns the last index on the current page.
     *
     * @return integer
     */
    public function getLastIndice()
    {
        if ($this->page * $this->maxPerPage >= $this->countResults) {
            return $this->countResults;
        } else {
            return $this->page * $this->maxPerPage;
        }
    }

    /**
     * Returns the number of results.
     *
     * @return integer
     */
    public function getCountResults()
    {
        return $this->countResults;
    }

    /**
     * Sets the number of results.
     *
     * @param integer $nb
     */
    protected function setCountResults($nb)
    {
        $this->countResults = $nb;
    }

    /**
     * Returns the first page number.
     *
     * @return integer
     */
    public function getFirstPage()
    {
        return 1;
    }

    /**
     * Returns the last page number.
     *
     * @return integer
     */
    public function getLastPage()
    {
        return $this->lastPage;
    }

    /**
     * Init the last page number.
     *
     */
    protected function initLastPage()
    {
        if ($this->getCountResults() > 0) {
            $lastPage = \ceil($this->getCountResults() / $this->getMaxPerPage());
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
     * @return integer
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Returns the next page.
     *
     * @return integer
     */
    public function getNextPage()
    {
        return min($this->getPage() + 1, $this->getLastPage());
    }

    /**
     * Returns the previous page.
     *
     * @return integer
     */
    public function getPreviousPage()
    {
        return max($this->getPage() - 1, $this->getFirstPage());
    }

    /**
     * Sets the current page.
     *
     * @param integer $page
     * @return AbstractPaginator
     */
    public function setPage($page)
    {
        $this->page = intval($page);

        if ($this->page <= 0) {
            $this->page = 1;
        }

        return $this;
    }

    /**
     * Returns the maximum number of results per page.
     *
     * @return integer
     */
    public function getMaxPerPage()
    {
        return $this->maxPerPage;
    }

    /**
     * Sets the maximum number of results per page.
     *
     * @param integer $max
     * @return AbstractPaginator
     */
    public function setMaxPerPage($max)
    {
        $max = intval($max);

        if ($max <= 0) {
            throw new \Exception('Max results value must be positive');
        }
        $this->maxPerPage = $max;

        return $this;
    }

    /**
     * Returns true if on the first page.
     *
     * @return boolean
     */
    public function isFirstPage()
    {
        return 1 == $this->page;
    }

    /**
     * Returns true if on the last page.
     *
     * @return boolean
     */
    public function isLastPage()
    {
        return $this->page == $this->lastPage;
    }

    /**
     * Returns true if the properties used for iteration have been initialized.
     *
     * @return boolean
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

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

class SimplePaginator extends AbstractPaginator
{

    protected $initialObjects = null;
    protected $manualCountResults = null;

    /**
     * {@inheritDoc}
     */
    public function init()
    {
        if ($this->initialObjects === null) {
            throw new \Exception('Results are required');
        }

        if (is_null($this->manualCountResults)) {
            $this->setCountResults(\count($this->initialObjects));
            $this->initLastPage();

            $offset = 0;
            $limit = 0;

            if ($this->getCountResults() > 0) {
                $offset = ($this->getPage() - 1) * $this->getMaxPerPage();
                $limit = $this->getMaxPerPage();
            }

            $this->results = \array_slice($this->initialObjects, $offset, $limit);
        } else {
            $this->setCountResults($this->manualCountResults);
            $this->initLastPage();

            $this->results = $this->initialObjects;
        }
    }

    /**
     * Set an array of results
     *
     * @param array|ArrayIterator $results
     * @return SimplePaginator
     */
    public function setResults($results)
    {
        if ($results instanceof \ArrayIterator) {
            $this->initialObjects = $results->getArrayCopy();
        } elseif (is_array($results)) {
            $this->initialObjects = $results;
        } else {
            throw new \Exception('Results must be an array');
        }

        return $this;
    }

    /**
     * Set an array of results without slice
     *
     * @param array|ArrayIterator $results
     * @param Int $manualCountResults
     */
    public function setResultsWithoutSlice($results, $manualCountResults)
    {
        $this->setResults($results);
        $this->manualCountResults = $manualCountResults;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getResults()
    {
        return new \ArrayIterator($this->results);
    }
}

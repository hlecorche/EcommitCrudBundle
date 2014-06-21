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

    protected $manualCountResults = null;

    /**
     * {@inheritDoc}
     */
    public function init()
    {
        if (is_null($this->objects) || !is_array($this->objects)) {
            throw new \Exception('Objects are required (array)');
        }

        if (is_null($this->manualCountResults)) {
            $this->setCountResults(\count($this->objects));

            $offset = 0;
            $limit = 0;
            if ($this->getPage() == 0 || $this->getMaxPerPage() == 0 || $this->getCountResults() == 0) {
                $this->setLastPage(0);
            } else {
                $this->setLastPage(\ceil($this->getCountResults() / $this->getMaxPerPage()));
                $offset = ($this->getPage() - 1) * $this->getMaxPerPage();

                $limit = $this->getMaxPerPage();
            }
            $this->objects = \array_slice($this->objects, $offset, $limit);
        } else {
            $this->setCountResults($this->manualCountResults);

            if ($this->getPage() == 0 || $this->getMaxPerPage() == 0 || $this->getCountResults() == 0) {
                $this->setLastPage(0);
            } else {
                $this->setLastPage(\ceil($this->getCountResults() / $this->getMaxPerPage()));
            }
        }
    }

    /**
     * Set an array of results
     *
     * @param array $results
     */
    public function setResults($results)
    {
        $this->resetIterator();
        $this->objects = $results;
    }

    /**
     * Set an array of results without slice
     *
     * @param array $results
     * @param Int $manualCountResults
     */
    public function setResultsWithoutSlice($results, $manualCountResults)
    {
        $this->resetIterator();
        $this->objects = $results;
        $this->manualCountResults = $manualCountResults;
    }

    /**
     * {@inheritDoc}
     */
    public function getResults()
    {
        return $this->objects;
    }

    /**
     * {@inheritDoc}
     */
    protected function retrieveObject($offset)
    {
        $results = $this->objects;

        return $results[$offset];
    }
}
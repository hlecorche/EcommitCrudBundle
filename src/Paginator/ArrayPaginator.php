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

class ArrayPaginator extends AbstractPaginator
{
    protected $initialObjects = null;
    protected $manualCountResults = null;

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        if (null === $this->initialObjects) {
            throw new \Exception('Results are required');
        }

        if (null === $this->manualCountResults) {
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
     * Set an array of results.
     *
     * @param array|ArrayIterator $results
     *
     * @return ArrayPaginator
     */
    public function setData($results)
    {
        if ($results instanceof \ArrayIterator) {
            $this->initialObjects = $results->getArrayCopy();
        } elseif (\is_array($results)) {
            $this->initialObjects = $results;
        } else {
            throw new \Exception('Results must be an array');
        }

        return $this;
    }

    /**
     * Set an array of results without slice.
     *
     * @param array|ArrayIterator $results
     * @param int                 $manualCountResults
     */
    public function setDataWithoutSlice($results, $manualCountResults)
    {
        $this->setData($results);
        $this->manualCountResults = $manualCountResults;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getResults()
    {
        return new \ArrayIterator($this->results);
    }
}

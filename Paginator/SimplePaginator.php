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

    /**
     * Initializes the pager.
     * 
     * Function to be called after parameters have been set.
     */
    public function init()
    {
        if (is_null($this->objects) || !is_array($this->objects))
        {
            throw new \Exception('Objects are required (array)');
        }
        $this->setNbResults(\count($this->objects));

        $offset = 0;
        $limit = 0;
        if ($this->getPage() == 0 || $this->getMaxPerPage() == 0 || $this->getNbResults() == 0)
        {
            $this->setLastPage(0);
        } else
        {
            $offset = ($this->getPage() - 1) * $this->getMaxPerPage();
            $this->setLastPage(\ceil($this->getNbResults() / $this->getMaxPerPage()));

            $limit = $this->getMaxPerPage();
        }
        $this->objects = \array_slice($this->objects, $offset, $limit);
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
     * Returns an array of results on the given page.
     * 
     * @param const $hydrationMode  Doctrine Hydration Mode
     * @return array 
     */
    public function getResults()
    {
        return $this->objects;
    }
    
    /**
     * Returns an object at a certain offset.
     * 
     * @param int $offset
     * @return mixed 
     */
    protected function retrieveObject($offset)
    {
        $results = $this->objects;
    return $results[$offset];
    }
}
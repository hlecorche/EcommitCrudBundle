<?php

/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) Hubert LECORCHE <hlecorche@e-commit.fr>
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
    protected $totalResults = null;
    
    /**
     * Initializes the pager.
     * 
     * Function to be called after parameters have been set.
     */
    public function init()
    {
        //Si le nombre de resultats n'est pas defini, on le calcule, sinon on le passe au pager
        if(is_null($this->totalResults))
	{
            $count = Paginate::count($this->query->getQuery());
            $this->setNbResults($count);
	}
	else
	{
            $this->setNbResults($this->totalResults);
	}
        
        $this->query->setFirstResult(0);
        $this->query->setMaxResults(0);
        if ($this->getPage() == 0 || $this->getMaxPerPage() == 0 || $this->getNbResults() == 0)
	{
            $this->setLastPage(0);
	}
	else
	{
            $offset = ($this->getPage() - 1) * $this->getMaxPerPage();
            $this->setLastPage(\ceil($this->getNbResults() / $this->getMaxPerPage()));
				
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
    public function getTotalResults()
    {
        return $this->totalResults;
    }

    /**
     * Sets manual total results
     * 
     * @param Int $totalResults 
     */
    public function setTotalResults($totalResults)
    {
        $this->totalResults = $totalResults;
    }
    
    /**
     * Returns an array of results on the given page.
     * 
     * @param const $hydrationMode  Doctrine Hydration Mode
     * @return array 
     */
    public function getResults($hydrationMode = Query::HYDRATE_OBJECT)
    {
        $query = $this->query->getQuery();
	return $query->execute(array(), $hydrationMode);
    }
    
    /**
     * Returns an object at a certain offset.
     * 
     * @param int $offset
     * @return mixed 
     */
    protected function retrieveObject($offset)
    {
        $query_retrieve = clone $this->query;	
        $query_retrieve->setFirstResult($offset - 1);
        $query_retrieve->setMaxResults(1);
        $results = $query_retrieve->getQuery()->execute();
        
	return $results[0];
    }
}
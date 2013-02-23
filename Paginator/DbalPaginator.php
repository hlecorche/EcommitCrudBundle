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

use Doctrine\DBAL\Query\QueryBuilder;
use Ecommit\CrudBundle\Paginator\DoctrinePaginator;

class DbalPaginator extends DoctrinePaginator
{
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
            $query_builder_count = clone $this->query;
            $query_builder_clone = clone $this->query;
            
            $query_builder_clone->resetQueryPart('orderBy'); //Desactivation du tri pour amélioration des perfs
            
            $query_builder_count->resetQueryParts(); //Suppression des parties DQL
            $query_builder_count->select('count(*)')
            ->from('('.$query_builder_clone->getSql().')', 'mainquery');
            
            $count = $query_builder_count->execute()->fetchColumn(0);
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
            $this->setLastPage(\ceil($this->getNbResults() / $this->getMaxPerPage()));
            $offset = ($this->getPage() - 1) * $this->getMaxPerPage();
                
            $this->query->setFirstResult($offset);
            $this->query->setMaxResults($this->getMaxPerPage());
        }
    }
    
    /**
     * Sets the QueryBuilder
     * 
     * @param QueryBuilder $query 
     */
    public function setDbalQueryBuilder(QueryBuilder $query)
    {
        $this->query = $query;
    }
    
    /**
     * Returns an array of results on the given page.
     * 
     * @return array 
     */
    public function getResults()
    {
        return $this->query->execute()->fetchAll();
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
        $results = $query_retrieve->execute()->fetchAll();
        
        return $results[0];
    }
}
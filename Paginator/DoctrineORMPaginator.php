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

use Doctrine\ORM\Tools\Pagination\Paginator;
use Ecommit\CrudBundle\DoctrineExtension\Paginate;

class DoctrineORMPaginator extends AbstractDoctrinePaginator
{
    protected $simplifiedRequest = true;
    protected $fetchJoinCollection = false;

    /**
     * {@inheritDoc}
     */
    protected function getQueryBuilderClass()
    {
        return 'Doctrine\ORM\QueryBuilder';
    }

    /**
     * {@inheritDoc}
     */
    public function initPaginator()
    {
        //Calculation of the number of lines
        if (is_null($this->manualCountResults)) {
            $count = Paginate::count($this->query->getQuery(), $this->simplifiedRequest);
            $this->setCountResults($count);
        } else {
            $this->setCountResults($this->manualCountResults);
        }
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
     * @return DoctrineORMPaginator
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
     * @return DoctrineORMPaginator
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

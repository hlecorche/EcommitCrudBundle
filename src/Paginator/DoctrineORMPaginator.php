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

use Doctrine\ORM\Tools\Pagination\Paginator;
use Ecommit\CrudBundle\DoctrineExtension\Paginate;

class DoctrineORMPaginator extends AbstractDoctrinePaginator
{
    protected $simplifiedRequest = true;
    protected $fetchJoinCollection = false;

    /**
     * {@inheritdoc}
     */
    protected function getQueryBuilderClass()
    {
        return 'Doctrine\ORM\QueryBuilder';
    }

    /**
     * {@inheritdoc}
     */
    public function initPaginator(): void
    {
        //Calculation of the number of lines
        if (null === $this->manualCountResults) {
            $countOptions = $this->countOptions;
            if (!isset($countOptions['behavior'])) {
                $countOptions['behavior'] = Paginate::getDefaultCountBehavior($this->query);
            }
            if ('orm' === $countOptions['behavior'] && !isset($countOptions['simplified_request'])) {
                $countOptions['simplified_request'] = $this->simplifiedRequest;
            }

            $count = Paginate::countQueryBuilder($this->query, $countOptions);
            $this->setCountResults($count);
        } else {
            $this->setCountResults($this->manualCountResults);
        }
    }

    /**
     * @return bool
     */
    public function isSimplifiedRequest()
    {
        return $this->simplifiedRequest;
    }

    /**
     * Use simplified request (not subrequest and not order by) or not when count results.
     *
     * @param bool $simplifiedRequest
     *
     * @return DoctrineORMPaginator
     */
    public function setSimplifiedRequest($simplifiedRequest)
    {
        $this->simplifiedRequest = $simplifiedRequest;

        return $this;
    }

    /**
     * @return bool
     */
    public function isFetchJoinCollection()
    {
        return $this->fetchJoinCollection;
    }

    /**
     * Set to true when fetch join a to-many collection
     * In that case 3 instead of the 2 queries described are executed.
     *
     * @param bool $fetchJoinCollection
     *
     * @return DoctrineORMPaginator
     */
    public function setFetchJoinCollection($fetchJoinCollection)
    {
        $this->fetchJoinCollection = $fetchJoinCollection;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getResults()
    {
        if (null === $this->results) {
            $query = $this->query->getQuery();
            $paginator = new Paginator($query, $this->fetchJoinCollection);
            $paginator->setUseOutputWalkers(!$this->simplifiedRequest);
            $this->results = $paginator->getIterator();
        }

        return $this->results;
    }
}

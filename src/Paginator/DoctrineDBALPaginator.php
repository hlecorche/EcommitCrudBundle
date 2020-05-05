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

use Ecommit\CrudBundle\DoctrineExtension\Paginate;

class DoctrineDBALPaginator extends AbstractDoctrinePaginator
{
    /**
     * {@inheritdoc}
     */
    protected function getQueryBuilderClass()
    {
        return 'Doctrine\DBAL\Query\QueryBuilder';
    }

    /**
     * {@inheritdoc}
     */
    public function initPaginator(): void
    {
        //Calculation of the number of lines
        if (null === $this->manualCountResults) {
            $count = Paginate::countQueryBuilder($this->query, $this->countOptions);
            $this->setCountResults($count);
        } else {
            $this->setCountResults($this->manualCountResults);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getResults()
    {
        if (null === $this->results) {
            $results = $this->query->execute()->fetchAll();
            $this->results = new \ArrayIterator($results);
        }

        return $this->results;
    }
}

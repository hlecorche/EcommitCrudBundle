<?php

/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\DoctrineExtension;

use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Ecommit\CrudBundle\Paginator\AbstractPaginator;
use Ecommit\CrudBundle\Paginator\ArrayPaginator;
use Ecommit\CrudBundle\Paginator\DoctrineDBALPaginator;
use Ecommit\CrudBundle\Paginator\DoctrineORMPaginator;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Paginate
{
    /**
     * Returns total results (SQL function "count")
     * 
     * @param Query $query
     * @param bool $simplifiedRequest  Use simplified request (not subrequest and not order by) or not
     * @return int
     */
    static public function count(Query $query, $simplifiedRequest = true)
    {
        //Must be deprecated in 2.4

        $doctrinePaginator = new Paginator($query);
        $doctrinePaginator->setUseOutputWalkers(!$simplifiedRequest);

        return $doctrinePaginator->count();
    }

    /**
     * @param \Doctrine\ORM\QueryBuilde|\Doctrine\DBAL\Query\QueryBuilder $queryBuilder
     * @param array $options
     * @return int
     * @throws \Exception
     */
    static public function countQueryBuilder($queryBuilder, array $options = array())
    {
        if ($queryBuilder instanceof \Doctrine\ORM\QueryBuilder) {
            $useORM = true;
        } elseif ($queryBuilder instanceof \Doctrine\DBAL\Query\QueryBuilder) {
            $useORM = false;
        } else {
            throw new \Exception('Bad QueryBuilder');
        }

        $resolver = new OptionsResolver();
        $resolver->setDefaults(array(
            //Behavior. Availabled values:
            //  - count_by_alias: Use alias. Option "alias" is required
            //  - count_by_sub_request: Use sub request
            //  - orm: Use Doctrine Paginator
            'behavior' => self::getDefaultCountBehavior($queryBuilder),
            //Used when behavior=count_by_alias
            'alias' => null,
            //Used when behavior=count_by_alias
            'distinct_alias' => true,
        ));
        $resolver->setAllowedTypes('distinct_alias', array('boolean'));
        if ($useORM) {
            $resolver->setAllowedValues('behavior', array('count_by_alias', 'count_by_sub_request', 'orm'));
            //Use only when ORM and behavior=orm
            $resolver->setDefault('simplified_request', true);
        } else {
            $resolver->setAllowedValues('behavior', array('count_by_alias', 'count_by_sub_request'));
        }
        $options = $resolver->resolve($options);
        if ('count_by_alias' === $options['behavior'] && null === $options['alias']) {
            throw new MissingOptionsException('Option "alias" is required');
        }

        if ($useORM) {
            if ('orm' === $options['behavior']) {
                $cloneQueryBuilder = clone $queryBuilder;
                $doctrinePaginator = new Paginator($cloneQueryBuilder->getQuery());
                $doctrinePaginator->setUseOutputWalkers(!$options['simplified_request']);

                return $doctrinePaginator->count();
            } elseif ('count_by_alias' === $options['behavior']) {
                /** @var \Doctrine\ORM\QueryBuilder $countQueryBuilder */
                $countQueryBuilder = clone $queryBuilder;
                $distinct = ($options['distinct_alias'])? 'DISTINCT ' : '';
                $countQueryBuilder->select(\sprintf('count(%s%s)', $distinct, $options['alias']));
                $countQueryBuilder->resetDQLPart('orderBy');

                return  $countQueryBuilder->getQuery()->getSingleScalarResult();
            } elseif ('count_by_sub_request' === $options['behavior']) {
                /** @var \Doctrine\ORM\QueryBuilder $cloneQueryBuilder */
                $cloneQueryBuilder = clone $queryBuilder;
                $cloneQueryBuilder->resetDQLPart('orderBy');
                $rsm = new ResultSetMapping();
                $rsm->addScalarResult('cnt', 'cnt');
                $countSql = \sprintf('SELECT count(*) as cnt FROM (%s) mainquery', $cloneQueryBuilder->getQuery()->getSQL());
                /** @var NativeQuery $countQuery */
                $countQuery = $queryBuilder->getEntityManager()->createNativeQuery($countSql, $rsm);
                $i = 0;
                /** @var Parameter $parameter */
                foreach ($queryBuilder->getParameters() as $parameter) {
                    $i++;
                    $countQuery->setParameter($i, $parameter->getValue(), $parameter->getType());
                }

                return $countQuery->getSingleScalarResult();
            }
        } else {
            if ('count_by_alias' === $options['behavior']) {
                /** @var \Doctrine\DBAL\Query\QueryBuilder $countQueryBuilder */
                $countQueryBuilder = clone $queryBuilder;
                $distinct = ($options['distinct_alias'])? 'DISTINCT ' : '';
                $countQueryBuilder->select(\sprintf('count(%s%s)', $distinct, $options['alias']));
                $countQueryBuilder->resetQueryPart('orderBy');

                return  $countQueryBuilder->execute()->fetchColumn(0);
            } elseif ('count_by_sub_request' === $options['behavior']) {
                $queryBuilderCount = clone $queryBuilder;
                $queryBuilderClone = clone $queryBuilder;

                $queryBuilderClone->resetQueryPart('orderBy'); //Disable sort (> performance)

                $queryBuilderCount->resetQueryParts(); //Remove Query Parts
                $queryBuilderCount->select('count(*)')
                    ->from('(' . $queryBuilderClone->getSql() . ')', 'mainquery');

                return $queryBuilderCount->execute()->fetchColumn(0);
            }
        }
    }

    /**
     * @param \Doctrine\ORM\QueryBuilde|\Doctrine\DBAL\Query\QueryBuilder $queryBuilder
     * @return string
     */
    public static function getDefaultCountBehavior($queryBuilder)
    {
        if ($queryBuilder instanceof \Doctrine\ORM\QueryBuilder) {
            return 'orm';
        } elseif ($queryBuilder instanceof \Doctrine\DBAL\Query\QueryBuilder) {
            return 'count_by_sub_request';
        }

        return null;
    }

    /**
     * @param \Doctrine\ORM\QueryBuilde|\Doctrine\DBAL\Query\QueryBuilder $queryBuilder
     * @param int $page Page number to display
     * @param int $perPage Results per page
     * @param array $options
     * @return AbstractPaginator
     * @throws \Exception
     */
    static public function createDoctrinePaginator($queryBuilder, $page, $perPage, array $options = array())
    {
        if ($queryBuilder instanceof \Doctrine\ORM\QueryBuilder) {
            $useORM = true;
        } elseif ($queryBuilder instanceof \Doctrine\DBAL\Query\QueryBuilder) {
            $useORM = false;
        } else {
            throw new \Exception('Bad QueryBuilder');
        }

        $resolver = new OptionsResolver();
        $resolver->setDefaults(array(
            //Behavior for create paginator. Availabled values :
            //  - doctrine_paginator: Return DoctrineORMPaginator or DoctrineDBALPaginator object
            //  - identifier_by_sub_request: Primary keys are found by sub request. Return ArrayPaginator. Option "identifier" is required
            'behavior' => 'doctrine_paginator',
            //Manual value for the number of results
            'count_manual_value' => null,
            //Count options. See countQueryBuilder. Used only if count_manual_value = null
            'count_options' => array(),
            //Identifier used when behavior=identifier_by_sub_request
            'identifier' => null,
        ));
        if ($useORM && 'doctrine_paginator' === $options['behavior']) {
            //Used only when ORM and behavior=doctrine_paginator
            $resolver->setDefault('simplified_request', true);
            $resolver->setDefault('fetch_join_collection', false);
        }
        $resolver->setAllowedValues('behavior', array('doctrine_paginator', 'identifier_by_sub_request'));
        $options = $resolver->resolve($options);

        if ('identifier_by_sub_request' === $options['behavior']) {
            if (null === $options['identifier']) {
                throw new MissingOptionsException('Option "identifier" is required');
            }
        }

        if ('doctrine_paginator' === $options['behavior']) {
            if ($useORM) {
                $paginator = new DoctrineORMPaginator($perPage);
                $paginator->setSimplifiedRequest($options['simplified_request']);
                $paginator->setFetchJoinCollection($options['fetch_join_collection']);
            } else {
                $paginator = new DoctrineDBALPaginator($perPage);
            }
            $paginator->setQueryBuilder($queryBuilder);
            $paginator->setPage($page);
            if (null === $options['count_manual_value']) {
                $paginator->setCountOptions($options['count_options']);
            } else {
                $paginator->setManualCountResults($options['count_manual_value']);
            }

            return $paginator;
        } elseif ('identifier_by_sub_request' === $options['behavior']) {
            $result = array();

            if (null === $options['count_manual_value']) {
                $countResults = self::countQueryBuilder($queryBuilder, $options['count_options']);
            } else {
                $countResults = $options['count_manual_value'];
            }

            if ($countResults) {
                $idsQueryBuilder = clone $queryBuilder;
                $idsQueryBuilder->select(\sprintf('DISTINCT %s as pk', $options['identifier']));

                if ($useORM) {
                    $tmpPaginator = new DoctrineORMPaginator($perPage);
                    $tmpPaginator->setSimplifiedRequest(false);
                    $tmpPaginator->setFetchJoinCollection(false);
                } else {
                    $tmpPaginator = new DoctrineDBALPaginator($perPage);
                }
                $tmpPaginator->setQueryBuilder($idsQueryBuilder);
                $tmpPaginator->setPage($page);
                $tmpPaginator->setManualCountResults($countResults);
                $tmpPaginator->init();

                $ids = array();
                foreach ($tmpPaginator->getResults() as $line) {
                    $ids[] = $line['pk'];
                }

                $finalQueryBuilder = clone $queryBuilder;
                if ($useORM) {
                    $finalQueryBuilder->resetDQLPart('where');
                    $finalQueryBuilder->setParameters(array());
                    QueryBuilderFilter::addMultiFilter($finalQueryBuilder, QueryBuilderFilter::SELECT_IN, $ids, $options['identifier'], 'paginate_pks');
                    $result = $finalQueryBuilder->getQuery()->getResult();
                } else {
                    $finalQueryBuilder->resetQueryPart('where');
                    $finalQueryBuilder->setParameters(array());
                    QueryBuilderFilter::addMultiFilter($finalQueryBuilder, QueryBuilderFilter::SELECT_IN, $ids, $options['identifier'], 'paginate_pks');
                    $result = $finalQueryBuilder->execute()->fetchAll();
                }
            }

            $paginator = new ArrayPaginator($perPage);
            $paginator->setPage($page);
            $paginator->setDataWithoutSlice($result, $countResults);

            return $paginator;
        }
    }
}

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
use Ecommit\CrudBundle\Paginator\DoctrineDBALPaginator;
use Ecommit\CrudBundle\Paginator\DoctrineORMPaginator;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Paginate
{
    /**
     * Returns total results (SQL function "count")
     * 
     * @param Query $query
     * @param bool $simplifiedRequest  Use simplified request (not subrequest and not order by) or not
     * @return int
     * @deprecated Deprecated since version 2.4. Use countQueryBuilder or Doctrine\ORM\Tools\Pagination\Paginator::count method instead.
     */
    static public function count(Query $query, $simplifiedRequest = true)
    {
        trigger_error('Paginate::count is deprecated since 2.4 version. Use countQueryBuilder or Doctrine\ORM\Tools\Pagination\Paginator::count method instead.', E_USER_DEPRECATED);

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
    static public function countQueryBuilder($queryBuilder, array $options)
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
            //  - count_by_alias: Use alias
            //  - count_by_sub_request: Use sub request
            //  - orm: Use Doctrine Paginator
            'behavior' => 'count_by_sub_request',
            //Used when behavior=count_by_alias
            'alias' => null,
        ));
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
                $countQueryBuilder->select(\sprintf('count(%s)', $options['alias']));
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
                $countQueryBuilder->select(\sprintf('count(%s)', $options['alias']));
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
     * @param array $options
     * @return AbstractPaginator
     * @throws \Exception
     */
    static public function createDoctrinePaginator($queryBuilder, array $options = array())
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
            'behavior' => 'doctrine_paginator',
            //Manual value for the number of results
            'count_manual_value' => null,
            //Behavior for count the number of results.
            //Unread option if count_manual_value not null. Availables values:
            //  - doctrine_paginator: Use internal system in DoctrineORMPaginator or DoctrineDBALPaginator. Used only if behavior=doctrine_paginator
            //  - manual: Use "count_manual_value" value option
            //  - internal: Use internal method (countQueryBuilder)
            'count_behavior' => function (Options $options, $previousValue) {
                if ('doctrine_paginator' === $options['behavior']) {
                    return 'doctrine_paginator';
                }

                return null;
            },
            //Internal method (countQueryBuilder) options. See countQueryBuilder. Used only if count_behavior = internal
            'count_internal_options' => array(),
            //Page number to display
            'page' => null,
            //Results per page
            'per_page' => null,
        ));
        if ($useORM) {
            //Used only when ORM and count_behavior=doctrine_paginator
            $resolver->setDefault('simplified_request', true);
            $resolver->setDefault('fetch_join_collection', false);
        }
        $resolver->setRequired('page');
        $resolver->setRequired('per_page');
        $resolver->setAllowedValues('behavior', array('doctrine_paginator'));
        $resolver->setAllowedValues('count_behavior', array('doctrine_paginator', 'manual', 'internal', null));
        $options = $resolver->resolve($options);

        if ('internal' === $options['count_behavior']) {
            if (count($options['count_internal_options']) === 0) {
                throw new MissingOptionsException('Option "count_internal_options" is required');
            }
        }
        if ('manual' === $options['count_behavior']) {
            if (null === $options['count_manual_value']) {
                throw new MissingOptionsException('Option "count_manual_value" is required');
            }
        }
        if ('doctrine_paginator' === $options['count_behavior'] && 'doctrine_paginator' !== $options['behavior']) {
            throw new InvalidOptionsException('count_behavior=doctrine_paginator not compatible when behavior!=doctrine_paginator');
        }

        if ('doctrine_paginator' === $options['behavior']) {
            if ($useORM) {
                $paginator = new DoctrineORMPaginator($options['per_page']);
                $paginator->setSimplifiedRequest($options['simplified_request']);
                $paginator->setFetchJoinCollection($options['fetch_join_collection']);
            } else {
                $paginator = new DoctrineDBALPaginator($options['per_page']);
            }
            $paginator->setQueryBuilder($queryBuilder);
            $paginator->setPage($options['page']);
            if ('internal' === $options['count_behavior']) {
                $paginator->setManualCountResults(self::countQueryBuilder($queryBuilder, $options['count_internal_options']));
            } elseif ('manual' === $options['count_behavior']) {
                $paginator->setManualCountResults($options['count_manual_value']);
            }

            return $paginator;
        } else {
            throw new \Exception('Not managed');
        }
    }
}

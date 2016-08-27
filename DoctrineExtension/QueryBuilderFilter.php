<?php
/**
 * This file is part of the QueryBuilderFilterTrait package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\DoctrineExtension;

class QueryBuilderFilter
{
    const SELECT_IN = 'IN';
    const SELECT_NOT_IN = 'NIN';
    const SELECT_ALL = 'ALL';

    /**
     * Add SQL WHERE IN or WHERE NOT IN filter
     * @param \Doctrine\DBAL\Query\QueryBuilder|\Doctrine\ORM\QueryBuilder $queryBuilder
     * @param string $filterSign  ALL (no filter), IN (WHERE IN), NIN (WHRE NOT IN)
     * @param array $filterValues  Values
     * @param string $sqlField  SQL field name
     * @param string $paramName SQL parameter name
     * @return \Doctrine\DBAL\Query\QueryBuilder|\Doctrine\ORM\QueryBuilder
     */
    public static function addMultiFilter($queryBuilder, $filterSign, $filterValues, $sqlField, $paramName)
    {
        if (($filterSign != self::SELECT_IN && $filterSign != self::SELECT_NOT_IN)
            || empty($filterValues) || 0 === count($filterValues)) {
            return $queryBuilder;
        }

        if (count($filterValues) > 1000) {
            return self::addGroupMultiFilter($queryBuilder, $filterSign, $filterValues, $sqlField, $paramName);
        }

        return self::addSimpleMultiFilter($queryBuilder, $filterSign, $filterValues, $sqlField, $paramName);
    }

    /**
     * Add SQL WHERE IN or WHERE NOT IN filter without group
     * @param \Doctrine\DBAL\Query\QueryBuilder|\Doctrine\ORM\QueryBuilder $queryBuilder
     * @param string $filterSign  ALL (no filter), IN (WHERE IN), NIN (WHRE NOT IN)
     * @param array $filterValues  Values
     * @param string $sqlField  SQL field name
     * @param string $paramName  SQL parameter name
     * @return \Doctrine\DBAL\Query\QueryBuilder|\Doctrine\ORM\QueryBuilder
     */
    public static function addSimpleMultiFilter($queryBuilder, $filterSign, $filterValues, $sqlField, $paramName)
    {
        $clauseSql = ($filterSign == self::SELECT_IN)? 'IN' : 'NOT IN';

        $queryBuilder->andWhere(\sprintf('%s %s (:%s)', $sqlField, $clauseSql, $paramName));
        $queryBuilder->setParameter($paramName, $filterValues, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY);

        return $queryBuilder;
    }

    /**
     * Add SQL WHERE IN or WHERE NOT IN filter with group
     * @param \Doctrine\DBAL\Query\QueryBuilder|\Doctrine\ORM\QueryBuilder $queryBuilder
     * @param string $filterSign  ALL (no filter), IN (WHERE IN), NIN (WHRE NOT IN)
     * @param array $filterValues  Values
     * @param string $sqlField  SQL field name
     * @param string $paramName  SQL parameter name
     * @return \Doctrine\DBAL\Query\QueryBuilder|\Doctrine\ORM\QueryBuilder
     */
    public static function addGroupMultiFilter($queryBuilder, $filterSign, $filterValues, $sqlField, $paramName)
    {
        $clauseSql = ($filterSign == self::SELECT_IN)? 'IN' : 'NOT IN';
        $separatorClauseSql = ($filterSign == self::SELECT_IN)? 'OR' : 'AND';

        $groupNumber = 0;
        $groups = array();
        foreach (\array_chunk($filterValues, 1000) as $filterValuesGroup) {
            $groupNumber++;
            $groups[] = \sprintf('%s %s (:%s%s)', $sqlField, $clauseSql, $paramName, $groupNumber);
            $queryBuilder->setParameter($paramName.$groupNumber, $filterValuesGroup, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
        }

        $queryBuilder->andWhere(implode(' '.$separatorClauseSql.' ', $groups));

        return $queryBuilder;
    }

    /**
     * Add SQL WHERE IN or WHERE NOT IN filter. Values must be in the whitelist or must not be in the blacklist
     * @param \Doctrine\DBAL\Query\QueryBuilder|\Doctrine\ORM\QueryBuilder $queryBuilder
     * @param string $filterSign  ALL (no filter), IN (WHERE IN), NIN (WHRE NOT IN)
     * @param array $filterValues  Values
     * @param string $sqlField  SQL field name
     * @param string $paramName  SQL parameter name
     * @param string $restrictSign IN (WHERE IN), NIN (WHRE NOT IN)
     * @param array $restrictValues Whitelist (if $restrictSign=IN) or blacklist (if $restrictSign=NIN)
     * @return \Doctrine\DBAL\Query\QueryBuilder|\Doctrine\ORM\QueryBuilder
     */
    public static function addMultiFilterWithRestrictValues($queryBuilder, $filterSign, $filterValues, $sqlField, $paramName, $restrictSign, $restrictValues)
    {
        if ((self::SELECT_IN === $restrictSign && 0 === count($restrictValues)) || (self::SELECT_IN === $filterSign && 0 === count($filterValues))) {
            //Must return no result
            $queryBuilder->andWhere('0 = 1');

            return $queryBuilder;
        }

        if (self::SELECT_IN === $restrictSign && self::SELECT_IN === $filterSign && count($filterValues) > 0 && count($restrictValues) > 0) {
            //We can simplify the query

            //Data cleaning
            $cleanValues = array();
            foreach ($filterValues as $value) {
                if (in_array($value, $restrictValues)) {
                    $cleanValues[] = $value;
                }
            }

            if (count($cleanValues) > 0) {
                $queryBuilder = self::addMultiFilter($queryBuilder, $filterSign, $cleanValues, $sqlField, $paramName);
            } elseif($filterSign == self::SELECT_IN) {
                //Must return no result
                $queryBuilder->andWhere('0 = 1');
            }
        } elseif (self::SELECT_NOT_IN === $restrictSign && self::SELECT_NOT_IN === $filterSign && count($filterValues) > 0 && count($restrictValues) > 0) {
            //We can simplify the query

            //Data fusion
            $cleanValues = $restrictValues;
            foreach ($filterValues as $value) {
                if (!in_array($value, $restrictValues)) {
                    $cleanValues[] = $value;
                }
            }

            $queryBuilder = self::addMultiFilter($queryBuilder, $filterSign, $cleanValues, $sqlField, $paramName);
        } else {
            //Two filters
            self::addMultiFilter($queryBuilder, $filterSign, $filterValues, $sqlField, $paramName);
            self::addMultiFilter($queryBuilder, $restrictSign, $restrictValues, $sqlField, $paramName);
        }

        return $queryBuilder;
    }

    /**
     * Add SQL "equal" or "not equal" filter
     * @param \Doctrine\DBAL\Query\QueryBuilder|\Doctrine\ORM\QueryBuilder $queryBuilder
     * @param bool $equal Equal or not
     * @param string $filterValue  Value
     * @param string $sqlField  SQL field name
     * @param string $paramName  SQL parameter name
     * @return \Doctrine\DBAL\Query\QueryBuilder|\Doctrine\ORM\QueryBuilder
     */
    public static function addEqualFilter($queryBuilder, $equal, $filterValue, $sqlField, $paramName)
    {
        if (empty($filterValue)) {
            return $queryBuilder;
        }

        if ($equal) {
            $queryBuilder->andWhere($sqlField.' = :'.$paramName);
        } else {
            $queryBuilder->andWhere($sqlField.' != :'.$paramName);
        }
        $queryBuilder->setParameter($paramName, $filterValue);

        return $queryBuilder;
    }

    /**
     * Add SQL comparator filter
     * @param \Doctrine\DBAL\Query\QueryBuilder|\Doctrine\ORM\QueryBuilder $queryBuilder
     * @param string $sign Comparator sign (< > <= >=)
     * @param string $filterValue  Value
     * @param string $sqlField  SQL field name
     * @param string $paramName  SQL parameter name
     * @return \Doctrine\DBAL\Query\QueryBuilder|\Doctrine\ORM\QueryBuilder
     */
    public static function addComparatorFilter($queryBuilder, $sign, $filterValue, $sqlField, $paramName)
    {
        if (empty($filterValue) && $filterValue !== 0) {
            return $queryBuilder;
        }

        $queryBuilder->andWhere(\sprintf('%s %s :%s', $sqlField, $sign, $paramName));
        $queryBuilder->setParameter($paramName, $filterValue);

        return $queryBuilder;
    }

    /**
     * Add SQL "LIKE" or "NOT LIKE" filter
     * @param \Doctrine\DBAL\Query\QueryBuilder|\Doctrine\ORM\QueryBuilder $queryBuilder
     * @param bool $contain  Contain or not
     * @param string $filterValue  Value
     * @param string $sqlField  SQL field name
     * @param string $paramName  SQL parameter name
     * @return \Doctrine\DBAL\Query\QueryBuilder|\Doctrine\ORM\QueryBuilder
     */
    public static function addContainFilter($queryBuilder, $contain, $filterValue, $sqlField, $paramName)
    {
        if (empty($filterValue)) {
            return $queryBuilder;
        }

        $filterValue = addcslashes($filterValue, '%_');
        if ($contain) {
            $queryBuilder->andWhere($queryBuilder->expr()->like($sqlField, ':' . $paramName));
        } else {
            $queryBuilder->andWhere($queryBuilder->expr()->notLike($sqlField, ':' . $paramName));
        }
        $queryBuilder->setParameter($paramName, '%'.$filterValue.'%');

        return $queryBuilder;
    }
}

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
     * @param string $filterSign  ALL (no filter), IN (WHERE IN), NIN (WHERE NOT IN)
     * @param array $filterValues  Values
     * @param string $sqlField  SQL field name
     * @param string $paramName SQL parameter name
     * @param bool $noResultIfNoInValue Return no result or not when $filterSign=IN and $filterValues is empty
     * @return \Doctrine\DBAL\Query\QueryBuilder|\Doctrine\ORM\QueryBuilder
     */
    public static function addMultiFilter($queryBuilder, $filterSign, $filterValues, $sqlField, $paramName, $noResultIfNoInValue = false)
    {
        if ($filterSign != self::SELECT_IN && $filterSign != self::SELECT_NOT_IN) {
            return $queryBuilder;
        }
        if (empty($filterValues) || 0 === count($filterValues)) {
            if (self::SELECT_NOT_IN == $filterSign || !$noResultIfNoInValue) {
                return $queryBuilder;
            }

            //Must return no result
            $queryBuilder->andWhere('0 = 1');

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
     * @param string $filterSign  ALL (no filter), IN (WHERE IN), NIN (WHERE NOT IN)
     * @param array $filterValues  Values
     * @param string $sqlField  SQL field name
     * @param string $paramName  SQL parameter name
     * @return \Doctrine\DBAL\Query\QueryBuilder|\Doctrine\ORM\QueryBuilder
     */
    protected static function addSimpleMultiFilter($queryBuilder, $filterSign, $filterValues, $sqlField, $paramName)
    {
        $clauseSql = ($filterSign == self::SELECT_IN)? 'IN' : 'NOT IN';

        $queryBuilder->andWhere(\sprintf('%s %s (:%s)', $sqlField, $clauseSql, $paramName));
        $queryBuilder->setParameter($paramName, $filterValues, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY);

        return $queryBuilder;
    }

    /**
     * Add SQL WHERE IN or WHERE NOT IN filter with group
     * @param \Doctrine\DBAL\Query\QueryBuilder|\Doctrine\ORM\QueryBuilder $queryBuilder
     * @param string $filterSign  ALL (no filter), IN (WHERE IN), NIN (WHERE NOT IN)
     * @param array $filterValues  Values
     * @param string $sqlField  SQL field name
     * @param string $paramName  SQL parameter name
     * @return \Doctrine\DBAL\Query\QueryBuilder|\Doctrine\ORM\QueryBuilder
     */
    protected static function addGroupMultiFilter($queryBuilder, $filterSign, $filterValues, $sqlField, $paramName)
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
     * Add SQL WHERE IN or WHERE NOT IN filter. And result MUST BE in the whitelist (if $restrictSign=IN) or MUST NOT BE in the blacklist (if $restrictSign=NIN)
     * @param \Doctrine\DBAL\Query\QueryBuilder|\Doctrine\ORM\QueryBuilder $queryBuilder
     * @param string $filterSign  ALL (no filter), IN (WHERE IN), NIN (WHERE NOT IN)
     * @param array $filterValues  Values
     * @param string $sqlField  SQL field name
     * @param string $paramName  SQL parameter name
     * @param string $restrictSign IN (WHERE IN), NIN (WHERE NOT IN)
     * @param array $restrictValues Whitelist (if $restrictSign=IN) or blacklist (if $restrictSign=NIN)
     * @param bool $noResultIfNoInValue Return no result or not when $filterSign=IN and $filterValues is empty
     * @param bool $noResultIfNoInRestrictValue Return no result or not when $restrictSign=IN and $restrictValues is empty
     * @return \Doctrine\DBAL\Query\QueryBuilder|\Doctrine\ORM\QueryBuilder
     */
    public static function addMultiFilterWithRestrictValues($queryBuilder, $filterSign, $filterValues, $sqlField, $paramName, $restrictSign, $restrictValues, $noResultIfNoInValue = false, $noResultIfNoInRestrictValue = true)
    {
        if (self::SELECT_IN === $restrictSign && self::SELECT_IN === $filterSign && count($filterValues) > 0 && count($restrictValues) > 0) {
            //We can simplify the query

            //Data cleaning
            $cleanValues = array();
            foreach ($filterValues as $value) {
                if (in_array($value, $restrictValues)) {
                    $cleanValues[] = $value;
                }
            }

            $queryBuilder = self::addMultiFilter($queryBuilder, $filterSign, $cleanValues, $sqlField, $paramName, true);
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
            self::addMultiFilter($queryBuilder, $filterSign, $filterValues, $sqlField, $paramName, $noResultIfNoInValue);
            self::addMultiFilter($queryBuilder, $restrictSign, $restrictValues, $sqlField, $paramName, $noResultIfNoInRestrictValue);
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

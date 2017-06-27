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
    const SELECT_IN = 'IN'; //WHERE IN
    const SELECT_NOT_IN = 'NIN'; //WHERE NOT IN
    const SELECT_ALL = 'ALL'; //No Filter (all values) 
    const SELECT_AUTO = 'AUT'; //WHERE IN. If filter values are empty, no filter (all values)

    /**
     * Add SQL WHERE IN or WHERE NOT IN filter
     * @param \Doctrine\DBAL\Query\QueryBuilder|\Doctrine\ORM\QueryBuilder $queryBuilder
     * @param string $filterSign  ALL (no filter), IN (WHERE IN), NIN (WHERE NOT IN), AUT (WHERE IN if $filterValues is not empty. No filter else)
     * @param array $filterValues  Values
     * @param string $sqlField  SQL field name
     * @param string $paramName SQL parameter name
     * @return \Doctrine\DBAL\Query\QueryBuilder|\Doctrine\ORM\QueryBuilder
     */
    public static function addMultiFilter($queryBuilder, $filterSign, $filterValues, $sqlField, $paramName)
    {
        if ($filterSign != self::SELECT_IN && $filterSign != self::SELECT_NOT_IN && $filterSign != self::SELECT_AUTO) {
            return $queryBuilder;
        }
        if (empty($filterValues) || 0 === count($filterValues)) {
            if (self::SELECT_NOT_IN == $filterSign || self::SELECT_AUTO == $filterSign) {
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
     * @param string $filterSign  ALL (no filter), IN (WHERE IN), NIN (WHERE NOT IN), AUT (WHERE IN if $filterValues is not empty. No filter else)
     * @param array $filterValues  Values
     * @param string $sqlField  SQL field name
     * @param string $paramName  SQL parameter name
     * @return \Doctrine\DBAL\Query\QueryBuilder|\Doctrine\ORM\QueryBuilder
     */
    protected static function addSimpleMultiFilter($queryBuilder, $filterSign, $filterValues, $sqlField, $paramName)
    {
        $clauseSql = ($filterSign == self::SELECT_IN || $filterSign == self::SELECT_AUTO)? 'IN' : 'NOT IN';

        $queryBuilder->andWhere(\sprintf('%s %s (:%s)', $sqlField, $clauseSql, $paramName));
        $queryBuilder->setParameter($paramName, $filterValues, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY);

        return $queryBuilder;
    }

    /**
     * Add SQL WHERE IN or WHERE NOT IN filter with group
     * @param \Doctrine\DBAL\Query\QueryBuilder|\Doctrine\ORM\QueryBuilder $queryBuilder
     * @param string $filterSign  ALL (no filter), IN (WHERE IN), NIN (WHERE NOT IN), AUT (WHERE IN if $filterValues is not empty. No filter else)
     * @param array $filterValues  Values
     * @param string $sqlField  SQL field name
     * @param string $paramName  SQL parameter name
     * @return \Doctrine\DBAL\Query\QueryBuilder|\Doctrine\ORM\QueryBuilder
     */
    protected static function addGroupMultiFilter($queryBuilder, $filterSign, $filterValues, $sqlField, $paramName)
    {
        $clauseSql = ($filterSign == self::SELECT_IN || $filterSign == self::SELECT_AUTO)? 'IN' : 'NOT IN';
        $separatorClauseSql = ($filterSign == self::SELECT_IN || $filterSign == self::SELECT_AUTO)? 'OR' : 'AND';

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
     * @param string $filterSign  ALL (no filter), IN (WHERE IN), NIN (WHERE NOT IN), AUT (WHERE IN if $filterValues is not empty. No filter else)
     * @param array $filterValues  Values
     * @param string $sqlField  SQL field name
     * @param string $paramName  SQL parameter name
     * @param string $restrictSign IN (WHERE IN), NIN (WHERE NOT IN), AUT (WHERE IN if $restrictValues is not empty. No filter else)
     * @param array $restrictValues Whitelist (if $restrictSign=IN or AUT) or blacklist (if $restrictSign=NIN)
     * @return \Doctrine\DBAL\Query\QueryBuilder|\Doctrine\ORM\QueryBuilder
     */
    public static function addMultiFilterWithRestrictValues($queryBuilder, $filterSign, $filterValues, $sqlField, $paramName, $restrictSign, $restrictValues)
    {
        if (in_array($restrictSign, array(self::SELECT_IN, self::SELECT_AUTO)) && in_array($filterSign, array(self::SELECT_IN, self::SELECT_AUTO)) && count($filterValues) > 0 && count($restrictValues) > 0) {
            //We can simplify the query

            //Data cleaning
            $cleanValues = array();
            foreach ($filterValues as $value) {
                if (in_array($value, $restrictValues)) {
                    $cleanValues[] = $value;
                }
            }

            $queryBuilder = self::addMultiFilter($queryBuilder, self::SELECT_IN, $cleanValues, $sqlField, $paramName);
        } elseif (self::SELECT_NOT_IN === $restrictSign && self::SELECT_NOT_IN === $filterSign && count($filterValues) > 0 && count($restrictValues) > 0) {
            //We can simplify the query

            //Data fusion
            $cleanValues = $restrictValues;
            foreach ($filterValues as $value) {
                if (!in_array($value, $restrictValues)) {
                    $cleanValues[] = $value;
                }
            }

            $queryBuilder = self::addMultiFilter($queryBuilder, self::SELECT_NOT_IN, $cleanValues, $sqlField, $paramName);
        } else {
            //Two filters
            self::addMultiFilter($queryBuilder, $filterSign, $filterValues, $sqlField, $paramName);
            self::addMultiFilter($queryBuilder, $restrictSign, $restrictValues, $sqlField, $paramName.'Restrict');
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

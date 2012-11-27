<?php

/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\Form\Filter;

use Symfony\Component\Form\FormBuilder;
use Doctrine\ORM\QueryBuilder;
use Ecommit\CrudBundle\Crud\CrudColumn;

class FieldFilterInteger extends FieldFilterAbstract
{
    const GREATER_THAN = '>';
    const GREATER_EQUAL = '>=';
    const SMALLER_THAN = '<';
    const SMALLER_EQUAL = '<=';
    const EQUAL = '=';
    
    protected $comparator;
    
    /**
     * {@inheritDoc} 
     */
    public function __construct($column_id, $field_name, $options = array(), $field_options = array())
    {
        if(!isset($options['comparator']))
        {
            throw new \Exception(\get_class($this).': Option "comparator" is required');
        }
        if(!\in_array($options['comparator'], array(self::EQUAL, self::GREATER_EQUAL, self::GREATER_THAN, self::SMALLER_EQUAL, self::SMALLER_THAN)))
        {
            throw new \Exception(\get_class($this).': Option "comparator": Bad value');
        }
        $this->comparator = $options['comparator'];
        
        parent::__construct($column_id, $field_name, $options, $field_options);
    }
    
    /**
     * {@inheritDoc} 
     */
    public function addField(FormBuilder $form_builder)
    {
        $form_builder->add($this->field_name, 'integer', $this->field_options);
        return $form_builder;
    }

    /**
     * {@inheritDoc} 
     */
    public function changeQuery(QueryBuilder $query_builder, FormFilterAbstract $form_data, CrudColumn $column)
    {
        $value_integer = $form_data->get($this->field_name);
        if(!is_null($value_integer) && is_numeric($value_integer))  //Important: Is_null but not is_empty 
        {
            $parameter_name = 'value_integer_'.str_replace(' ', '', $this->field_name);
            $query_builder->andWhere(sprintf('%s %s :%s', $this->getAliasSearch($column), $this->comparator, $parameter_name))
            ->setParameter($parameter_name, $value_integer);
        }
        return $query_builder;
    }
}
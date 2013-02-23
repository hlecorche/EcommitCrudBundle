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
use Ecommit\CrudBundle\Crud\CrudColumn;

class FieldFilterBoolean extends FieldFilterAbstract
{
    protected $value_true;
    protected $value_false;
    protected $not_null_is_true;
    protected $null_is_false;
    
    /**
     * {@inheritDoc} 
     */
    public function __construct($column_id, $field_name, $options = array(), $field_options = array())
    {
        $field_options['multiple'] = false;
        $field_options['choices'] = self::getChoices();
        $this->value_true = isset($options['value_true'])? $options['value_true'] : 1;
        $this->value_false = isset($options['value_false'])? $options['value_false'] : 0;
        $this->not_null_is_true = isset($options['not_null_is_true'])? $options['not_null_is_true'] : false;
        $this->null_is_false = isset($options['null_is_false'])? $options['null_is_false'] : true;
        
        parent::__construct($column_id, $field_name, $options, $field_options);
    }
    
    /**
     * {@inheritDoc} 
     */
    public function addField(FormBuilder $form_builder)
    {
        $form_builder->add($this->field_name, 'choice', $this->field_options);
        return $form_builder;
    }
    
    /**
     * {@inheritDoc} 
     */
    public function changeQuery($query_builder, FormFilterAbstract $form_data, CrudColumn $column)
    {
        $value_bool = $form_data->get($this->field_name);
        if(empty($value_bool) || !is_scalar($value_bool))
        {
            return $query_builder;
        }
        
        if($value_bool == 'T')
        {
            $parameter_name = 'value_true'.str_replace(' ', '', $this->field_name);
            if($this->not_null_is_true)
            {
                $parameter_name_false = 'value_false'.str_replace(' ', '', $this->field_name);
                $query_builder->andWhere(sprintf('(%s = :%s OR (%s IS NOT NULL AND %s != :%s))',$this->getAliasSearch($column), $parameter_name, $this->getAliasSearch($column), $this->getAliasSearch($column), $parameter_name_false))
                ->setParameter($parameter_name, $this->value_true)
                ->setParameter($parameter_name_false, $this->value_false);  
            }
            else
            {
                $query_builder->andWhere(sprintf('%s = :%s',$this->getAliasSearch($column), $parameter_name))
                ->setParameter($parameter_name, $this->value_true);
            }
            return $query_builder;
        }
        elseif($value_bool == 'F')
        {
            $parameter_name = 'value_false'.str_replace(' ', '', $this->field_name);
            if(is_null($this->value_false))
            {
                $query_builder->andWhere(sprintf('%s IS NULL',$this->getAliasSearch($column)));
            }
            elseif($this->null_is_false)
            {
                $query_builder->andWhere(sprintf('(%s = :%s OR %s IS NULL)',$this->getAliasSearch($column), $parameter_name, $this->getAliasSearch($column)))
                ->setParameter($parameter_name, $this->value_false);
            }
            else
            {
                $query_builder->andWhere(sprintf('%s = :%s',$this->getAliasSearch($column), $parameter_name))
                ->setParameter($parameter_name, $this->value_false);
            }
            return $query_builder;
        }
        else
        {
            return $query_builder;
        }
    }
    
    public static function getChoices()
    {
        return array(
            'T' => 'filter.true',
            'F' => 'filter.false',
        );
    }
}
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

class FormFilterList extends FormFilterAbstract
{
    protected $multiple;
    protected $limit = 99;
    
    /**
     * {@inheritDoc} 
     */
    public function __construct($column_id, $field_name, $options = array(), $field_options = array())
    {
        if(!isset($options['choices']))
        {
            throw new \Exception(\get_class($this).': Option "choices" is required');
        }
        if(!is_array($options['choices']))
        {
            throw new \Exception(\get_class($this).': Option "choices": Bad format (array required)');
        }
        $field_options['choices'] = $options['choices'];
        $this->multiple = isset($options['multiple'])? $options['multiple'] : false;
        $field_options['multiple'] = $this->multiple;
        if(!empty($options['limit']))
        {
            $this->limit = $options['limit'];
        }
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
    public function changeQuery(QueryBuilder $query_builder, FilterTypeAbstract $type, CrudColumn $column)
    {
        $value_list = $type->get($this->field_name);
        if(empty($value_list))
        {
            return $query_builder;
        }
        
        if($this->multiple)
        {
            if(!is_array($value_list))
            {
                $value_list = array($value_list);
            }
            if(count($value_list) > $this->limit)
            {
                return $query_builder; 
            }
            $query_builder->andWhere($query_builder->expr()->in($this->getAliasSearch($column), $value_list));
        }
        else
        {
            if(is_array($value_list))
            {
                return $query_builder;
            }
            $parameter_name = 'value_list'.str_replace(' ', '', $this->field_name);
            $query_builder->andWhere(sprintf('%s = :%s',$this->getAliasSearch($column), $parameter_name))
            ->setParameter($parameter_name, $value_list);
        }
        return $query_builder;
    }
}
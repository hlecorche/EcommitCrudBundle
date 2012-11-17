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

class FormFilterAutoComplete extends FormFilterAbstract
{
    protected $multiple;
    protected $limit = 50;
    
    /**
     * {@inheritDoc} 
     */
    public function __construct($column_id, $field_name, $options = array(), $field_options = array())
    {
        if(empty($options['class']))
        {
            throw new \Exception(\get_class($this).'"class" option is required');
        }
        $field_options['class'] = $options['class'];
        
        if(!empty($options['query_builder']))
        {
            $field_options['query_builder'] = $options['query_builder'];
        }
        
        if(!empty($options['url']))
        {
            $field_options['url'] = $options['url'];
        }
        else
        {
            throw new \Exception(\get_class($this).'"url" option is required');
        }
        
        if(!empty($options['alias']))
        {
            $field_options['alias'] = $options['alias'];
        }
        
        if(!empty($options['key_method']))
        {
            $field_options['key_method'] = $options['key_method'];
        }
        if(!empty($options['method']))
        {
            $field_options['method'] = $options['method'];
        }
        
        $this->multiple = isset($options['multiple'])? $options['multiple'] : false;
        if($this->multiple)
        {
            if(!empty($options['limit']))
            {
                $field_options['max'] = $options['limit'];
                $this->limit = $options['limit'];
            }
        }
        
        $field_options['input'] = 'key';
        
        parent::__construct($column_id, $field_name, $options, $field_options);
    }
    
    /**
     * {@inheritDoc} 
     */
    public function addField(FormBuilder $form_builder)
    {
        if($this->multiple)
        {
            $form_builder->add($this->field_name, 'multi_entity_autocomplete', $this->field_options);
        }
        else
        {
            $form_builder->add($this->field_name, 'entity_autocomplete', $this->field_options);
        }
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
            $parameter_name = 'value_autocomplete'.str_replace(' ', '', $this->field_name);
            $query_builder->andWhere(sprintf('%s = :%s',$this->getAliasSearch($column), $parameter_name))
            ->setParameter($parameter_name, $value_list);
        }
        return $query_builder;
    }
}
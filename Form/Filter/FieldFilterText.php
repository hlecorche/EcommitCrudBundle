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

class FieldFilterText extends FieldFilterAbstract
{
    protected $must_begin;
    protected $must_end;
    
    /**
     * {@inheritDoc} 
     */
    public function __construct($column_id, $field_name, $options = array(), $field_options = array())
    {
       $this->must_begin = isset($options['must_begin'])? $options['must_begin'] : false;
       $this->must_end = isset($options['must_end'])? $options['must_end'] : false;
     
       parent::__construct($column_id, $field_name, $options, $field_options);
    }
    
    /**
     * {@inheritDoc} 
     */
    public function addField(FormBuilder $form_builder)
    {
        $form_builder->add($this->field_name, 'text', $this->field_options);
        return $form_builder;
    }

    /**
     * {@inheritDoc} 
     */
    public function changeQuery(QueryBuilder $query_builder, FormFilterAbstract $form_data, CrudColumn $column)
    {
        $value_text = $form_data->get($this->field_name);
        $parameter_name = 'value_integer_'.str_replace(' ', '', $this->field_name);
        if(empty($value_text) || !is_scalar($value_text))
        {
            return $query_builder;
        }
        
        if($this->must_begin && $this->must_end)
        {
            $query_builder->andWhere(sprintf('%s = :%s', $this->getAliasSearch($column), $parameter_name))
            ->setParameter($parameter_name, $value_text);
        }
        else
        {
            $after = ($this->must_begin)? '' : '%';
            $before = ($this->must_end)? '' : '%';
            $value_text = addcslashes($value_text, '%_');
            $like = $after.$value_text.$before;
            $query_builder->andWhere($query_builder->expr()->like($this->getAliasSearch($column), ':'.$parameter_name))
            ->setParameter($parameter_name, $like);
        }
        return $query_builder;
    }
}
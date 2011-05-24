<?php

/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) Hubert LECORCHE <hlecorche@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\Form\Filter;

use Symfony\Component\Form\FormBuilder;
use Doctrine\ORM\QueryBuilder;
use Ecommit\CrudBundle\Crud\CrudColumn;

class FormFilterText extends FormFilterAbstract
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
    public function changeQuery(QueryBuilder $query_builder, FilterTypeAbstract $type, CrudColumn $column)
    {
        $value_text = $type->get($this->field_name);
        if(empty($value_text) || !is_scalar($value_text))
        {
            return $query_builder;
        }
        
        $after = ($this->must_begin)? '' : '%';
        $before = ($this->must_end)? '' : '%';
        $value_text = addcslashes($value_text, '%_');
        $like = $query_builder->expr()->literal($after.$value_text.$before);
        $query_builder->andWhere($query_builder->expr()->like($column->alias, $like));
        return $query_builder;
    }
}
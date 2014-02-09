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

use Ecommit\CrudBundle\Crud\CrudColumn;
use Ecommit\CrudBundle\Form\Searcher\FormSearcherAbstract;
use Symfony\Component\Form\FormBuilder;

class FieldFilterEmpty extends FieldFilterAbstract
{
    /**
     * {@inheritDoc} 
     */
    public function __construct($column_id, $field_name, $options = array(), $field_options = array())
    {
        $field_options['value'] = 1;
        parent::__construct($column_id, $field_name, $options, $field_options);
    }
    
    /**
     * {@inheritDoc} 
     */
    public function addField(FormBuilder $form_builder)
    {
        $form_builder->add($this->field_name, 'checkbox', $this->field_options);
        return $form_builder;
    }

    /**
     * {@inheritDoc} 
     */
    public function changeQuery($query_builder, FormSearcherAbstract $form_data, CrudColumn $column)
    {
        $value_empty = $form_data->get($this->field_name);
        if(empty($value_empty) || !is_scalar($value_empty))
        {
            return $query_builder;
        }
        
        if($value_empty == 1)
        {
            $query_builder->andWhere(sprintf('(%s IS NULL OR %s = \'\')',$this->getAliasSearch($column), $this->getAliasSearch($column)));
        }
        return $query_builder;
    }
}
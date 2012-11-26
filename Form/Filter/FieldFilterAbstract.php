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

abstract class FieldFilterAbstract
{
    protected $column_id;
    protected $field_name;
    protected $field_options;
    
    /**
     * Adds the field into the form
     * 
     * @param FormBuilder $form_builder
     * @return FormBuilder
     */
    abstract public function addField(FormBuilder $form_builder);
    
    /**
     * Changes the query
     * 
     * @param QueryBuilder $query_builder
     * @param FormFilterAbstract $type
     * @param CrudColumn $column
     * @return QueryBuilder
     */
    abstract public function changeQuery(QueryBuilder $query_builder, FormFilterAbstract $type, CrudColumn $column);
    
    /**
     * Constructor
     * 
     * @param string $column_id   Column id
     * @param string $field_name   Field Name (search form)
     * @param array $options   Options
     * @param array $field_options   Field options
     */
    public function __construct($column_id, $field_name, $options = array(), $field_options = array())
    {
        $this->column_id = $column_id;
        $this->field_name = $field_name;
        
        if(!isset($field_options['required']))
        {
            $field_options['required'] = false;
        }
        $this->field_options = $field_options;
    }
    
    /**
     * Returns the column id associated at this object
     * 
     * @return string 
     */
    public function getColumnId()
    {
        return $this->column_id;
    }
    
    /**
     * Returns the SQL alias used for search query
     * 
     * @param CrudColumn $column
     * @return string 
     */
    public function getAliasSearch(CrudColumn $column)
    {
        if(empty($column->alias_search))
        {
            return $column->alias;
        }
        return $column->alias_search;
    }
}
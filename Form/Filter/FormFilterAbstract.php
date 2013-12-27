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

use Symfony\Component\Form\FormBuilderInterface;

abstract class FormFilterAbstract
{
    protected $fields_filter;

    /**
     * Declares fields
     * 
     * @return array
     */
    abstract public function configureFieldsFilter();
    
    /**
     * Gets field value
     * 
     * @param string $field   Field Name
     * @return mixed 
     */
    public function get($field)
    {
        if(isset($this->$field))
        {
            return $this->$field;
        }
        return null;
    }
    
    /**
     * Clears this objet
     * Used before storing this object in session
     * If one propertie is not public and it doesn't bebin
     * by "field_", it will be deleted
     */
    public function clear()
    {
        foreach($this as $key => $value)
        {
            $variable = new \ReflectionProperty($this, $key);
            if(!$variable->isPublic() && !\preg_match('/^field_/', $key))
            {
                unset($this->$key);
            }
        }
    }
    
    /**
     * Returns fields
     * 
     * @return array 
     */
    public function getFieldsFilter($registry = null)
    {
        if(!$this->fields_filter)
        {
            $this->fields_filter = array();
            foreach($this->configureFieldsFilter() as $field)
            {
                $this->fields_filter[] = $field;
                if(!empty($registry) && $field instanceof \Ecommit\CrudBundle\Form\Filter\FieldFilterDoctrineInterface)
                {
                    $field->setRegistry($registry);
                }
            }
        }
        return $this->fields_filter;
    }
    
    /**
     * Changes the form (global change)
     * 
     * @param \Symfony\Component\Form\FormBuilderInterface $form_builder
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    public function globalBuildForm(FormBuilderInterface $form_builder)
    {
        return $form_builder;
    }
    
    /**
     * Changes the query (global change)
     * 
     * @param QueryBuilder $query_builder
     * @return QueryBuilder
     */
    public function globalChangeQuery($query_builder)
    {
        return $query_builder;
    }
}
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

class FormFilterDate extends FormFilterAbstract
{
    const GREATER_THAN = '>';
    const GREATER_EQUAL = '>=';
    const SMALLER_THAN = '<';
    const SMALLER_EQUAL = '<=';
    const EQUAL = '=';
    
    protected $type;
	protected $comparator;
    
    /**
     * {@inheritDoc} 
     */
    public function __construct($column_id, $field_name, $options = array(), $field_options = array())
    {
        $type = (isset($options['type']))? $options['type'] : 'js_date';
		if($type != 'js_date' && $type != 'date')
		{
			throw new Exception(\get_class($this).': Option "type" is not valid');
		}
		$this->type = $type;
		
		if(!isset($options['comparator']))
        {
            throw new \Exception(\get_class($this).': Option "comparator" is required');
        }
        if(!\in_array($options['comparator'], array(self::EQUAL, self::GREATER_EQUAL, self::GREATER_THAN, self::SMALLER_EQUAL, self::SMALLER_THAN)))
        {
            throw new \Exception(\get_class($this).': Option "comparator": Bad value');
        }
        $this->comparator = $options['comparator'];
        
        $field_options['input'] = 'datetime';
        parent::__construct($column_id, $field_name, $options, $field_options);
    }
    
    /**
     * {@inheritDoc} 
     */
    public function addField(FormBuilder $form_builder)
    {
        $form_builder->add($this->field_name, $this->type, $this->field_options);
        return $form_builder;
    }
    
    /**
     * {@inheritDoc} 
     */
    public function changeQuery(QueryBuilder $query_builder, FilterTypeAbstract $type, CrudColumn $column)
    {
        $value_date = $type->get($this->field_name);
        if(!empty($value_date) && $value_date instanceof \DateTime)
        {
            $value_date = $value_date->format('Y-m-d H:i:s');
            $parameter_name = 'value_date_'.str_replace(' ', '', $this->field_name);
            $query_builder->andWhere(sprintf('%s %s :%s', $column->alias, $this->comparator, $parameter_name))
            ->setParameter($parameter_name, $value_date);
        }
        return $query_builder;
    }
}
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

class FieldFilterDate extends FieldFilterAbstract
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
    public function changeQuery($query_builder, FormSearcherAbstract $form_data, CrudColumn $column)
    {
        $value_date = $form_data->get($this->field_name);
        if(!empty($value_date) && $value_date instanceof \DateTime)
        {
            $parameter_name = 'value_date_'.str_replace(' ', '', $this->field_name);
            
            switch($this->comparator):
                case FieldFilterDate::SMALLER_THAN:
                case FieldFilterDate::GREATER_EQUAL:
                    $value_date->setTime(0, 0, 0);
                    $value_date = $value_date->format('Y-m-d H:i:s');
                    $query_builder->andWhere(sprintf('%s %s :%s', $this->getAliasSearch($column), $this->comparator, $parameter_name))
                    ->setParameter($parameter_name, $value_date);
                    break;
                case FieldFilterDate::SMALLER_EQUAL:
                case FieldFilterDate::GREATER_THAN:
                    $value_date->setTime(23, 59, 59);
                    $value_date = $value_date->format('Y-m-d H:i:s');
                    $query_builder->andWhere(sprintf('%s %s :%s', $this->getAliasSearch($column), $this->comparator, $parameter_name))
                    ->setParameter($parameter_name, $value_date);
                    break;
                default:
                    $value_date_inf = clone $value_date;
                    $value_date_sup = clone $value_date;
                    $value_date_inf->setTime(0, 0, 0);
                    $value_date_sup->setTime(23, 59, 59);
                    $value_date_inf = $value_date_inf->format('Y-m-d H:i:s');
                    $value_date_sup = $value_date_sup->format('Y-m-d H:i:s');
                    $parameter_name_inf = 'value_date_inf_'.str_replace(' ', '', $this->field_name);
                    $parameter_name_sup = 'value_date_sup_'.str_replace(' ', '', $this->field_name);
                    $query_builder->andWhere(sprintf('%s >= :%s AND %s <= :%s', $this->getAliasSearch($column), $parameter_name_inf, $this->getAliasSearch($column), $parameter_name_sup))
                    ->setParameter($parameter_name_inf, $value_date_inf)
                    ->setParameter($parameter_name_sup, $value_date_sup);
                    break;
            endswitch;
        }
        return $query_builder;
    }
}
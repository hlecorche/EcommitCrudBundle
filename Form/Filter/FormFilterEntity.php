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

class FormFilterEntity extends FormFilterList
{
    /**
     * {@inheritDoc} 
     */
    public function __construct($column_id, $field_name, $options = array(), $field_options = array())
    {
		$key_method = isset($options['key_method'])? $options['key_method'] : 'getId';
        $method = isset($options['method'])? $options['method'] : '__toString';
        
        
        if(!empty($options['query_builder']))
        {
            $query_builder = $options['query_builder'];
            if(!($query_builder instanceof \Doctrine\ORM\QueryBuilder))
            {
                throw new \Exception(\get_class($this).': Option "query_builder": Bad format (\Doctrine\ORM\QueryBuilder required)');
            }
            $query = $query_builder->getQuery();
        }
        elseif(!empty($options['class']) && !empty($options['em']))
        {
            $em = $options['em'];
            if(!($em instanceof \Doctrine\ORM\EntityManager))
            {
                throw new \Exception(\get_class($this).': Option "em": Bad format (\Doctrine\ORM\EntityManager required)');
            }
            $query = $em->createQueryBuilder();
            $query = $query->from($options['class'], 'c')
            ->select('c')
            ->getQuery();
        }
        else
        {
            throw new \Exception(\get_class($this).': Options "class / em" or "query_builder" are required');
        }
        
        
        $choices = array();
        foreach($query->execute() as $choice)
        {
            $choices[$choice->$key_method()] = $choice->$method();
        }
        $options['choices'] = $choices;
        
        parent::__construct($column_id, $field_name, $options, $field_options);
    }
}
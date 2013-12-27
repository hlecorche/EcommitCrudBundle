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

class FieldFilterEntity extends FieldFilterList implements FieldFilterDoctrineInterface
{
    protected $registry;
    
    protected $key_method;
    protected $method;
    protected $query_builder;
    protected $class;
    protected $em;

    /**
     * {@inheritDoc} 
     */
    public function __construct($column_id, $field_name, $options = array(), $field_options = array())
    {
        $this->key_method = isset($options['key_method'])? $options['key_method'] : 'getId';
        $this->method = isset($options['render_method'])? $options['render_method'] : '__toString';
        
        if(empty($options['class']))
        {
            throw new \Exception(\get_class($this).': Options "class" is required');
        }
        $this->class = $options['class'];
        
        if(!empty($options['query_builder']))
        {
            $this->query_builder = $options['query_builder'];
        }
        if(!empty($options['em']))
        {
            $this->em = $options['em'];
        }
        
        $options['choices'] = array();
        
        parent::__construct($column_id, $field_name, $options, $field_options);
    }
    
    /**
     * {@inheritDoc} 
     */
    public function addField(FormBuilder $form_builder)
    {
        if(empty($this->em))
        {
            $em = $this->registry->getManagerForClass($this->class);
        }
        else
        {
            $em = $this->registry->getManager($this->em);
        }
        
        $query_builder = $this->query_builder;
        if(empty($query_builder))
        {
            $query_builder= $em->createQueryBuilder()
            ->from($this->class, 'c')
            ->select('c');
        }
            
        if($query_builder instanceof \Closure)
        {
            $query_builder = $query_builder($em->getRepository($this->class));
        }        
        if(!$query_builder instanceof \Doctrine\ORM\QueryBuilder)
        {
            throw new \Exception(\get_class($this).': "query_builder" must be an instance of Doctrine\ORM\QueryBuilder');
        }
        
        $choices = array();
        $key_method = $this->key_method;
        $method = $this->method;
        foreach($query_builder->getQuery()->execute() as $choice)
        {
            $choices[$choice->$key_method()] = $choice->$method();
        }
        
        $this->field_options['choices'] = $choices;
        return parent::addField($form_builder);
    }

    public function getRegistry()
    {
        return $this->registry;
    }

    public function setRegistry(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }
}
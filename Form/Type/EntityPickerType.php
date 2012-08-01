<?php

/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Exception\FormException;
use Doctrine\ORM\EntityManager;
use Ecommit\JavascriptBundle\jQuery\Manager;
use Ecommit\JavascriptBundle\Form\DataTransformer\EntityToAutoCompleteTransformer;

class EntityPickerType extends AbstractType
{
    protected $javascript_manager;
    protected $em;
    
    /**
     * Constructor
     * 
     * @param Manager $javascript_manager
     * @param EntityManager $em
     */
    public function __construct(Manager $javascript_manager, EntityManager $em)
    {
        $this->javascript_manager = $javascript_manager;
        $this->em = $em;
    }
    
    
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('key', 'hidden');
        $builder->add('text', 'text');
        
        $required_options = array('list_url', 'alias', 'modal_id');
        if($options['add_enabled'])
        {
            $required_options[] = 'add_url';
        }
        foreach($required_options as $required_option)
        {
            if(empty($options[$required_option]))
            {
                throw new FormException(sprintf('The "%s" option is required', $required_option));
            }
        }
        
        if(!empty($options['query_builder']))
        {
            $query_builder = $options['query_builder'];
            $alias = $options['alias'];
        }
        elseif(!empty($options['class']))
        {
            $query_builder = $this->em->createQueryBuilder()
            ->from($options['class'], 'c')
            ->select('c');
            $alias = 'c.'.$options['alias'];
        }
        else
        {
            throw new FormException('"query_builder" or "class" option is required');
        }
        
        $builder->appendClientTransformer(new EntityToAutoCompleteTransformer($query_builder, $alias, $options['method'], $options['key_method']));
        
        $builder->setAttribute('list_url', $options['list_url']);
        $builder->setAttribute('list_ajax_options', $options['list_ajax_options']);
        $builder->setAttribute('add_enabled', $options['add_enabled']);
        $builder->setAttribute('add_url', $options['add_url']);
        $builder->setAttribute('add_ajax_options', $options['add_ajax_options']);
        $builder->setAttribute('modal_id', $options['modal_id']);
        $builder->setAttribute('image_add', $options['image_add']);
        $builder->setAttribute('image_list', $options['image_list']);
    }

    
    public function buildView(FormView $view, FormInterface $form)
    {
        $this->javascript_manager->enablejQueryTools();
        
        $view->set('list_url', $form->getAttribute('list_url'));
        $view->set('list_ajax_options', $form->getAttribute('list_ajax_options'));
        $view->set('add_enabled', $form->getAttribute('add_enabled'));
        $view->set('add_url', $form->getAttribute('add_url'));
        $view->set('add_ajax_options', $form->getAttribute('add_ajax_options'));
        $view->set('modal_id', $form->getAttribute('modal_id'));
        $view->set('image_add', $form->getAttribute('image_add'));
        $view->set('image_list', $form->getAttribute('image_list'));
    }
    
    
    public function getParent(array $options)
    {
        return 'form';
    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'list_url'          => null,
            'list_ajax_options' => null,
            'add_enabled'       => true,
            'add_url'           => null,
            'add_ajax_options'  => null,
            'modal_id'          => null,
            'em'                => $this->em,
            'class'             => null,
            'query_builder'     => null,
            'alias'             => null,
            'method'            => '__toString',
            'key_method'        => 'getId',
            'image_add'         => 'ecr/images/i16/add.png',
            'image_list'        => 'ecr/images/i16/form_search.png',
            
            'error_bubbling'    => false,
        );
    }

    public function getName()
    {
        return 'entity_picker';
    }
}
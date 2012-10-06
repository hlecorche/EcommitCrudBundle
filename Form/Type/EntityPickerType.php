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

use Doctrine\ORM\QueryBuilder;
use Ecommit\JavascriptBundle\Form\DataTransformer\EntityToAutoCompleteTransformer;
use Ecommit\JavascriptBundle\jQuery\Manager;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\FormException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class EntityPickerType extends AbstractType
{
    protected $javascript_manager;
    protected $registry;
    
    /**
     * Constructor
     * 
     * @param Manager $javascript_manager
     * @param ManagerRegistry $registry
     */
    public function __construct(Manager $javascript_manager, ManagerRegistry $registry)
    {
        $this->javascript_manager = $javascript_manager;
        $this->registry = $registry;
    }
    
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('key', 'hidden');
        $builder->add('text', 'text');
        
        $builder->addViewTransformer(new EntityToAutoCompleteTransformer($options['query_builder'], $options['alias'], $options['method'], $options['key_method']));
    }

    
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $this->javascript_manager->enablejQueryTools();
        
        $view->vars['list_url'] = $options['list_url'];
        $view->vars['list_ajax_options'] = $options['list_ajax_options'];
        $view->vars['add_enabled'] = $options['add_enabled'];
        $view->vars['add_url'] = $options['add_url'];
        $view->vars['add_ajax_options'] = $options['add_ajax_options'];
        $view->vars['modal_id'] = $options['modal_id'];
        $view->vars['image_add'] = $options['image_add'];
        $view->vars['image_list'] = $options['image_list'];
        $view->vars['label_add'] = $options['label_add'];
        $view->vars['label_list'] = $options['label_list'];
    }
    
    
    public function getParent()
    {
        return 'form';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $registry = $this->registry;
        $em_normalizer = function (Options $options, $em) use ($registry)
        {
            if(null !== $em)
            {
                return $registry->getManager($em);
            }
            return $registry->getManagerForClass($options['class']);
        };

        $query_builder_normalizer = function (Options $options, $query_builder)
        {
            $em = $options['em'];
            $class = $options['class'];
            if($query_builder == null)
            {
                $query_builder= $em->createQueryBuilder()
                ->from($class, 'c')
                ->select('c');
            }
            
            if($query_builder instanceof \Closure)
            {
                $query_builder = $query_builder($em->getRepository($class));
            }        
            if(!$query_builder instanceof QueryBuilder)
            {
                throw new FormException('"query_builder" must be an instance of Doctrine\ORM\QueryBuilder');
            }
            return $query_builder;
        };
        
        $alias_normalizer = function (Options $options, $alias)
        {
            if($alias == null)
            {
                $em = $options['em'];
                $identifier = $em->getClassMetadata($options['class'])->getIdentifierFieldNames();
                if(count($identifier) != 1)
                {
                    throw new FormException('"alias" option is required');
                }
                $identifier = $identifier[0];
                $query_builder = $options['query_builder'];
                $alias = current($query_builder->getRootAliases()).'.'.$identifier;
            }
            return $alias;
        };
        
        $resolver->setDefaults(array(
            'list_ajax_options' => null,
            'add_enabled'       => true,
            'add_url'           => null,
            'add_ajax_options'  => null,
            'em'                => null,
            'query_builder'     => null,
            'alias'             => null,
            'method'            => '__toString',
            'key_method'        => 'getId',
            'image_add'         => 'ecr/images/i16/add.png',
            'image_list'        => 'ecr/images/i16/form_search.png',
            'label_add'         => 'picker.add',
            'label_list'        => 'picker.list',
            
            'error_bubbling'    => false,
        ));
        
        $resolver->setRequired(array(
            'class',
            'list_url',
            'modal_id',
        ));
        
        $resolver->setNormalizers(array(
            'em' => $em_normalizer,
            'query_builder' => $query_builder_normalizer,
            'alias' => $alias_normalizer,
        ));
    }

    public function getName()
    {
        return 'entity_picker';
    }
}
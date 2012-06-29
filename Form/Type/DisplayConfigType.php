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
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Ecommit\CrudBundle\Crud\CrudManager;

class DisplayConfigType extends AbstractType
{
    protected $crud;
    
    /**
     * Constructor
     * 
     * @param CrudManager $crud 
     */
    public function __construct(CrudManager $crud)
    {
        $this->crud = $crud;
    }
    
    /**
     * {@inheritDoc} 
     */
    function buildForm(FormBuilderInterface $builder, array $options)
    {
        //Field "npp"
        $choices_npp = array();
        foreach($this->crud->getAvailableNumberResultsDisplayed() as $number)
        {
            $choices_npp[$number] = $number;
        }
        $builder->add('npp', 'choice', array('choices' => $choices_npp,
                                             'label' => 'Number of results per page',
                                             'required' => false)); //required:  W3C validation
        
        //Field "displayed_columns"
        $choice_columns = array();
        foreach($this->crud->getColumns() as $column)
        {
            $choice_columns[$column->id] = $column->label;
        }
        $builder->add('displayed_columns', 'choice', array('choices' => $choice_columns,
                                                           'multiple' => true, 'expanded' => true,
                                                           'label' => 'Columns to be shown'));
        
        //Default values
        $values['npp'] = $this->crud->getSessionValues()->number_results_displayed;
        $values['displayed_columns'] = $this->crud->getSessionValues()->columns_diplayed;
        $builder->setData($values);
    }
    
    /**
     * {@inheritDoc} 
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => false,
        ));
    }
    
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'crud_display_config';
    }
}

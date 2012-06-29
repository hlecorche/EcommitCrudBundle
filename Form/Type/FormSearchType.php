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

class FormSearchType extends AbstractType
{
    /**
     * {@inheritDoc} 
     */
    function buildForm(FormBuilderInterface $builder, array $options)
    {  
    }
    
    /**
     * {@inheritDoc} 
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Ecommit\CrudBundle\Form\Filter\FilterTypeAbstract',
            'csrf_protection' => false,
            'required' => false,
        ));
    }
    
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'crud_search';
    }
}
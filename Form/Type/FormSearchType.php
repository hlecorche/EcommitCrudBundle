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
use Symfony\Component\OptionsResolver\OptionsResolver;

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
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Ecommit\CrudBundle\Form\Searcher\AbstractFormSearcher',
            'csrf_protection' => false,
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

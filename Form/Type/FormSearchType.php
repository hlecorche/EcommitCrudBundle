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

class FormSearchType extends AbstractType
{
    /**
     * {@inheritDoc} 
     */
    function buildForm(FormBuilder $builder, array $options)
    {  
    }
    
    /**
     * {@inheritDoc} 
     */
    public function getDefaultOptions(array $options)
    {
        return array(
            'csrf_protection' => false,
            'required' => false,
        );
    }
    
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'crud_search';
    }
}
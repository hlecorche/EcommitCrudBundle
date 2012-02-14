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
use Doctrine\ORM\QueryBuilder;
use Ecommit\CrudBundle\Crud\CrudColumn;

class FormFilterNumber extends FormFilterInteger
{
    /**
     * {@inheritDoc} 
     */
    public function addField(FormBuilder $form_builder)
    {
        $form_builder->add($this->field_name, 'number', $this->field_options);
        return $form_builder;
    }
}
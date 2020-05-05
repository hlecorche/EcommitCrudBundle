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

use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilder;

class FieldFilterNumber extends FieldFilterInteger
{
    /**
     * {@inheritDoc} 
     */
    public function addField(FormBuilder $formBuilder)
    {
        $formBuilder->add($this->property, NumberType::class, $this->typeOptions);

        return $formBuilder;
    }
}

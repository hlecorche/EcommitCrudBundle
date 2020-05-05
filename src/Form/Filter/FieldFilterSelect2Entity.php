<?php
/**
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\Form\Filter;

use Ecommit\JavascriptBundle\Form\Type\Select2\Select2ChoiceType;
use Symfony\Component\Form\FormBuilder;

class FieldFilterSelect2Entity extends FieldFilterEntity
{
    /**
     * {@inheritDoc}
     */
    public function addField(FormBuilder $formBuilder)
    {
        $formBuilder->add($this->property, Select2ChoiceType::class, $this->typeOptions);

        return $formBuilder;
    }
}

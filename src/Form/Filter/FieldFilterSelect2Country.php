<?php

declare(strict_types=1);

/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\Form\Filter;

use Ecommit\JavascriptBundle\Form\Type\Select2\Select2CountryType;
use Symfony\Component\Form\FormBuilder;

class FieldFilterSelect2Country extends FieldFilterChoice
{
    /**
     * {@inheritdoc}
     */
    public function addField(FormBuilder $formBuilder)
    {
        $formBuilder->add($this->property, Select2CountryType::class, $this->typeOptions);

        return $formBuilder;
    }
}

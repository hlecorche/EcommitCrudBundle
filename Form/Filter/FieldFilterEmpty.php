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

use Ecommit\CrudBundle\Form\Searcher\AbstractFormSearcher;
use Symfony\Component\Form\FormBuilder;

class FieldFilterEmpty extends AbstractFieldFilter
{
    /**
     * {@inheritDoc}
     */
    protected function configureTypeOptions($typeOptions)
    {
        $typeOptions['value'] = 1;

        return $typeOptions;
    }

    /**
     * {@inheritDoc}
     */
    public function addField(FormBuilder $formBuilder)
    {
        $formBuilder->add($this->property, 'checkbox', $this->typeOptions);

        return $formBuilder;
    }

    /**
     * {@inheritDoc}
     */
    public function changeQuery($queryBuilder, AbstractFormSearcher $formData, $aliasSearch)
    {
        $value = $formData->get($this->property);
        if (empty($value) || !is_scalar($value)) {
            return $queryBuilder;
        }

        if ($value == 1) {
            $queryBuilder->andWhere(
                sprintf('(%s IS NULL OR %s = \'\')', $aliasSearch, $aliasSearch)
            );
        }

        return $queryBuilder;
    }
}
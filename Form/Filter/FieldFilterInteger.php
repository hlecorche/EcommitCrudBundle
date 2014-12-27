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
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FieldFilterInteger extends AbstractFieldFilter
{
    const GREATER_THAN = '>';
    const GREATER_EQUAL = '>=';
    const SMALLER_THAN = '<';
    const SMALLER_EQUAL = '<=';
    const EQUAL = '=';

    /**
     * {@inheritDoc}
     */
    protected function configureOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(
            array(
                'comparator',
            )
        );

        $resolver->setAllowedValues(
            'comparator',
            array(
                self::EQUAL,
                self::GREATER_EQUAL,
                self::GREATER_THAN,
                self::SMALLER_EQUAL,
                self::SMALLER_THAN,
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function addField(FormBuilder $formBuilder)
    {
        $formBuilder->add($this->property, 'integer', $this->typeOptions);

        return $formBuilder;
    }

    /**
     * {@inheritDoc}
     */
    public function changeQuery($queryBuilder, AbstractFormSearcher $formData, $aliasSearch)
    {
        $value = $formData->get($this->property);
        if (!is_null($value) && is_numeric($value)) { //Important: Is_null but not is_empty
            $parameterName = 'value_integer_' . str_replace(' ', '', $this->property);
            $queryBuilder->andWhere(
                sprintf('%s %s :%s', $aliasSearch, $this->options['comparator'], $parameterName)
            )
                ->setParameter($parameterName, $value);
        }

        return $queryBuilder;
    }
}

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

use Ecommit\CrudBundle\Form\Searcher\AbstractFormSearcher;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FieldFilterInteger extends AbstractFieldFilter
{
    public const GREATER_THAN = '>';
    public const GREATER_EQUAL = '>=';
    public const SMALLER_THAN = '<';
    public const SMALLER_EQUAL = '<=';
    public const EQUAL = '=';

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(
            [
                'comparator',
            ]
        );

        $resolver->setAllowedValues(
            'comparator',
            [
                self::EQUAL,
                self::GREATER_EQUAL,
                self::GREATER_THAN,
                self::SMALLER_EQUAL,
                self::SMALLER_THAN,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function addField(FormBuilder $formBuilder)
    {
        $formBuilder->add($this->property, IntegerType::class, $this->typeOptions);

        return $formBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function changeQuery($queryBuilder, AbstractFormSearcher $formData, $aliasSearch)
    {
        $value = $formData->get($this->property);
        if (null !== $value && is_numeric($value)) { //Important: Is_null but not is_empty
            $parameterName = 'value_integer_'.str_replace(' ', '', $this->property);
            $queryBuilder->andWhere(
                sprintf('%s %s :%s', $aliasSearch, $this->options['comparator'], $parameterName)
            )
                ->setParameter($parameterName, $value);
        }

        return $queryBuilder;
    }
}

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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FieldFilterBoolean extends AbstractFieldFilter
{
    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'value_true' => 1,
                'value_false' => 0,
                'not_null_is_true' => false,
                'null_is_false' => true,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function configureTypeOptions($typeOptions)
    {
        $typeOptions['multiple'] = false;
        $typeOptions['choices'] = self::getChoices();
        if (!isset($typeOptions['placeholder']) && !$typeOptions['required']) {
            $typeOptions['placeholder'] = 'filter.choices.placeholder';
        }

        return $typeOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function addField(FormBuilder $formBuilder)
    {
        $formBuilder->add($this->property, ChoiceType::class, $this->typeOptions);

        return $formBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function changeQuery($queryBuilder, AbstractFormSearcher $formData, $aliasSearch)
    {
        $value = $formData->get($this->property);
        if (empty($value) || !is_scalar($value)) {
            return $queryBuilder;
        }

        if ('T' == $value) {
            $parameterName = 'value_true'.str_replace(' ', '', $this->property);
            if ($this->options['not_null_is_true']) {
                $parameterNameFalse = 'value_false'.str_replace(' ', '', $this->property);
                $queryBuilder->andWhere(
                    sprintf(
                        '(%s = :%s OR (%s IS NOT NULL AND %s != :%s))',
                        $aliasSearch,
                        $parameterName,
                        $aliasSearch,
                        $aliasSearch,
                        $parameterNameFalse
                    )
                )
                    ->setParameter($parameterName, $this->options['value_true'])
                    ->setParameter($parameterNameFalse, $this->options['value_false']);
            } else {
                $queryBuilder->andWhere(sprintf('%s = :%s', $aliasSearch, $parameterName))
                    ->setParameter($parameterName, $this->options['value_true']);
            }

            return $queryBuilder;
        } elseif ('F' == $value) {
            $parameterName = 'value_false'.str_replace(' ', '', $this->property);
            if (null === $this->options['value_false']) {
                $queryBuilder->andWhere(sprintf('%s IS NULL', $aliasSearch));
            } elseif ($this->options['null_is_false']) {
                $queryBuilder->andWhere(
                    sprintf(
                        '(%s = :%s OR %s IS NULL)',
                        $aliasSearch,
                        $parameterName,
                        $aliasSearch
                    )
                )
                    ->setParameter($parameterName, $this->options['value_false']);
            } else {
                $queryBuilder->andWhere(sprintf('%s = :%s', $aliasSearch, $parameterName))
                    ->setParameter($parameterName, $this->options['value_false']);
            }

            return $queryBuilder;
        }

        return $queryBuilder;
    }

    public static function getChoices()
    {
        return [
            'filter.true' => 'T',
            'filter.false' => 'F',
        ];
    }
}

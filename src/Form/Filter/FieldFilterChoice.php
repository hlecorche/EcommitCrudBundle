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

use Ecommit\CrudBundle\DoctrineExtension\QueryBuilderFilter;
use Ecommit\CrudBundle\Form\Searcher\AbstractFormSearcher;
use Ecommit\UtilBundle\Util\Util;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class FieldFilterChoice extends AbstractFieldFilter
{
    /**
     * {@inheritdoc}
     */
    protected function configureCommonOptions(OptionsResolver $resolver): void
    {
        parent::configureCommonOptions($resolver);

        $resolver->setDefaults(
            [
                'multiple' => false,
                'min' => null,
                'max' => 99,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'choices' => null,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function configureTypeOptions($typeOptions)
    {
        $typeOptions['choices'] = $this->options['choices'];
        $typeOptions['multiple'] = $this->options['multiple'];
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
    protected function getAutoConstraints()
    {
        if ($this->options['multiple']) {
            return [
                new Assert\Count(
                    [
                        'min' => $this->options['min'],
                        'max' => $this->options['max'],
                    ]
                ),
            ];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function changeQuery($queryBuilder, AbstractFormSearcher $formData, $aliasSearch)
    {
        $value = $formData->get($this->property);
        $parameterName = 'value_choice'.str_replace(' ', '', $this->property);
        if (null === $value || '' === $value || [] === $value) {
            return $queryBuilder;
        }

        if ($this->options['multiple']) {
            if (!\is_array($value)) {
                $value = [$value];
            }
            $value = Util::filterScalarValues($value);
            if (\count($value) > $this->options['max'] || 0 === \count($value)) {
                return $queryBuilder;
            }
            if ($this->options['min'] && \count($value) < $this->options['min']) {
                return $queryBuilder;
            }
            QueryBuilderFilter::addMultiFilter($queryBuilder, QueryBuilderFilter::SELECT_IN, $value, $aliasSearch, $parameterName);
        } else {
            if (!is_scalar($value)) {
                return $queryBuilder;
            }
            $queryBuilder->andWhere(sprintf('%s = :%s', $aliasSearch, $parameterName))
                ->setParameter($parameterName, $value);
        }

        return $queryBuilder;
    }
}

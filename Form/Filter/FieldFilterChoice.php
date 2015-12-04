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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class FieldFilterChoice extends AbstractFieldFilter
{
    /**
     * {@inheritDoc}
     */
    protected function configureCommonOptions(OptionsResolver $resolver)
    {
        parent::configureCommonOptions($resolver);

        $resolver->setDefaults(
            array(
                'multiple' => false,
                'min' => null,
                'max' => 99,
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'choices' => null,
                'choices_as_values' => true,
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function configureTypeOptions($typeOptions)
    {
        $typeOptions['choices'] = $this->options['choices'];
        $typeOptions['choices_as_values'] = $this->options['choices_as_values'];
        $typeOptions['multiple'] = $this->options['multiple'];
        if (!isset($typeOptions['placeholder']) && !$typeOptions['required']) {
            $typeOptions['placeholder'] = 'filter.choices.placeholder';
        }

        return $typeOptions;
    }


    /**
     * {@inheritDoc}
     */
    public function addField(FormBuilder $formBuilder)
    {
        $formBuilder->add($this->property, ChoiceType::class, $this->typeOptions);

        return $formBuilder;
    }

    /**
     * {@inheritDoc}
     */
    protected function getAutoConstraints()
    {
        if ($this->options['multiple']) {
            return array(
                new Assert\Count(
                    array(
                        'min' => $this->options['min'],
                        'max' => $this->options['max'],
                    )
                ),
            );
        } else {
            return array();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function changeQuery($queryBuilder, AbstractFormSearcher $formData, $aliasSearch)
    {
        $value = $formData->get($this->property);
        $parameterName = 'value_choice' . str_replace(' ', '', $this->property);
        if (empty($value)) {
            return $queryBuilder;
        }

        if ($this->options['multiple']) {
            if (!is_array($value)) {
                $value = array($value);
            }
            if (count($value) > $this->options['max']) {
                return $queryBuilder;
            }
            if ($this->options['min'] && count($value) < $this->options['min']) {
                return $queryBuilder;
            }
            $queryBuilder->andWhere($queryBuilder->expr()->in($aliasSearch, ':' . $parameterName))
                ->setParameter($parameterName, $value);
        } else {
            if (is_array($value)) {
                return $queryBuilder;
            }
            $queryBuilder->andWhere(sprintf('%s = :%s', $aliasSearch, $parameterName))
                ->setParameter($parameterName, $value);
        }

        return $queryBuilder;
    }
}

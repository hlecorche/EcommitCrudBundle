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
use Ecommit\JavascriptBundle\Form\Type\TokenInputEntitiesAjaxType;
use Ecommit\ScalarValues\ScalarValues;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class FieldFilterTokenInputEntitiesAjax extends AbstractFieldFilter
{
    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'em' => null,
                'query_builder' => null,
                'choice_label' => null,
                'identifier' => null,
                'url' => null, //Required in FormType if route_name is empty
                'route_name' => null,
                'route_params' => null,
                'min' => null,
                'max' => 99,
            ]
        );

        $resolver->setRequired(
            [
                'class',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function configureTypeOptions($typeOptions)
    {
        foreach ($this->options as $optionName => $optionValue) {
            if (!empty($optionValue) && !\in_array($optionName, ['validate', 'min'])) {
                $typeOptions[$optionName] = $optionValue;
            }
        }
        $typeOptions['input'] = 'key';

        return $typeOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function addField(FormBuilder $formBuilder)
    {
        $formBuilder->add($this->property, TokenInputEntitiesAjaxType::class, $this->typeOptions);

        return $formBuilder;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAutoConstraints()
    {
        return [
            new Assert\Count(
                [
                    'min' => $this->options['min'],
                    'max' => $this->options['max'],
                ]
            ),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function changeQuery($queryBuilder, AbstractFormSearcher $formData, $aliasSearch)
    {
        $value = $formData->get($this->property);
        $parameterName = 'value_choice'.str_replace(' ', '', $this->property);
        if (null === $value || '' === $value || !\is_array($value)) {
            return $queryBuilder;
        }
        $value = ScalarValues::filterScalarValues($value);
        if (0 === \count($value)) {
            return $queryBuilder;
        }

        if (\count($value) > $this->options['max']) {
            return $queryBuilder;
        }
        if ($this->options['min'] && \count($value) < $this->options['min']) {
            return $queryBuilder;
        }

        return $queryBuilder->andWhere($queryBuilder->expr()->in($aliasSearch, ':'.$parameterName))
            ->setParameter($parameterName, $value);
    }
}

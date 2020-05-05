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
use Ecommit\JavascriptBundle\Form\Type\JqueryAutocompleteEntityAjaxType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class FieldFilterJqueryAutocompleteEntityAjax extends AbstractFieldFilter
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
                'max_length' => 255,
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
            if (!empty($optionValue) && !\in_array($optionName, ['validate', 'max_length'])) {
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
        $formBuilder->add($this->property, JqueryAutocompleteEntityAjaxType::class, $this->typeOptions);

        return $formBuilder;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAutoConstraints()
    {
        return [
            new Assert\Length(
                [
                    'max' => $this->options['max_length'],
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
        $parameterName = 'value_jquery_auto'.str_replace(' ', '', $this->property);
        if (null === $value || '' === $value || !is_scalar($value)) {
            return $queryBuilder;
        }

        return $queryBuilder->andWhere(sprintf('%s = :%s', $aliasSearch, $parameterName))
            ->setParameter($parameterName, $value);
    }
}

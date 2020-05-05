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

use Ecommit\JavascriptBundle\Form\Type\Select2\Select2EntityAjaxType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FieldFilterSelect2EntityAjax extends FieldFilterChoice
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
        $formBuilder->add($this->property, Select2EntityAjaxType::class, $this->typeOptions);

        return $formBuilder;
    }
}

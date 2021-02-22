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

use Ecommit\CrudBundle\Form\DataTransformer\Entity\EntitiesToIdsTransformer;
use Ecommit\CrudBundle\Form\DataTransformer\Entity\EntityToIdTransformer;
use Ecommit\CrudBundle\Form\Type\EntityAjaxType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\ReversedTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FieldFilterEntityAjax extends FieldFilterChoice
{
    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'route_params' => [],
        ]);

        $resolver->setRequired([
            'class',
            'route_name',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureTypeOptions($typeOptions)
    {
        $options = ['class', 'route_name', 'route_params', 'multiple'];
        foreach ($options as $option) {
            if (!isset($typeOptions[$option])) {
                $typeOptions[$option] = $this->options[$option];
            }
        }
        if (!isset($typeOptions['max_elements'])) {
            $typeOptions['max_elements'] = $this->options['max'];
        }

        return $typeOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function addField(FormBuilder $formBuilder)
    {
        $formBuilder->add($this->property, EntityAjaxType::class, $this->typeOptions);
        $typeOptions = $formBuilder->get($this->property)->getOptions();
        if ($this->options['multiple']) {
            $formBuilder->get($this->property)->addModelTransformer(
                new ReversedTransformer(
                    new EntitiesToIdsTransformer($typeOptions['query_builder'], $typeOptions['identifier'], $typeOptions['choice_label'], false, $typeOptions['max_elements'])
                )
            );
        } else {
            $formBuilder->get($this->property)->addModelTransformer(
                new ReversedTransformer(
                    new EntityToIdTransformer($typeOptions['query_builder'], $typeOptions['identifier'], $typeOptions['choice_label'], false)
                )
            );
        }

        return $formBuilder;
    }
}

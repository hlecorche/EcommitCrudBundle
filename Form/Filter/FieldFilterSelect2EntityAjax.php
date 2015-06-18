<?php
/**
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\Form\Filter;

use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FieldFilterSelect2EntityAjax extends FieldFilterChoice
{
    /**
     * {@inheritDoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'em' => null,
                'query_builder' => null,
                'property' => null, // deprecated since 2.2, use "choice_label"
                'choice_label' => function (Options $options) {
                    // BC with the "property" option
                    if ($options['property']) {
                        trigger_error('The "property" option is deprecated since version 2.2. Use "choice_label" instead.', E_USER_DEPRECATED);

                        return $options['property'];
                    }

                    return null;
                },
                'identifier' => null,
                'url' => null, //Required in FormType if route_name is empty
                'route_name' => null,
                'route_params' => null,
            )
        );

        $resolver->setRequired(
            array(
                'class',
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function configureTypeOptions($typeOptions)
    {
        foreach ($this->options as $optionName => $optionValue) {
            if (!empty($optionValue) && !in_array($optionName, array('validate', 'min'))) {
                $typeOptions[$optionName] = $optionValue;
            }
        }
        $typeOptions['input'] = 'key';

        return $typeOptions;
    }

    /**
     * {@inheritDoc}
     */
    public function addField(FormBuilder $formBuilder)
    {
        $formBuilder->add($this->property, 'ecommit_javascript_select2entityajax', $this->typeOptions);

        return $formBuilder;
    }
}

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

use Ecommit\CrudBundle\Form\Searcher\AbstractFormSearcher;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FieldFilterJqueryAutocompleteEntityAjax extends FieldFilterChoice
{
    /**
     * {@inheritDoc}
     */
    protected function configureOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'em' => null,
                'query_builder' => null,
                'property' => null,
                'identifier' => null,
            )
        );

        $resolver->setRequired(
            array(
                'class',
                'url',
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function configureTypeOptions($typeOptions)
    {
        foreach ($this->options as $optionName => $optionValue) {
            if (!empty($optionValue)) {
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
        $formBuilder->add($this->property, 'ecommit_javascript_jqueryautocompleteentityajax', $this->typeOptions);

        return $formBuilder;
    }

    /**
     * {@inheritDoc}
     */
    public function changeQuery($queryBuilder, AbstractFormSearcher $formData, $aliasSearch)
    {
        //Force no multiple values
        $this->options['multiple'] = false;

        return parent::changeQuery($queryBuilder, $formData, $aliasSearch);
    }
}
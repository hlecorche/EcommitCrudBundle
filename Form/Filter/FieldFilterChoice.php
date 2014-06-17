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
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FieldFilterChoice extends AbstractFieldFilter
{
    /**
     * {@inheritDoc}
     */
    protected function configureOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'choices' => null,
                'choice_list' => null,
                'multiple' => false,
                'max' => 99,
            )
        );

        $resolver->setAllowedTypes(
            array(
                'choice_list' => array('null', 'Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceListInterface'),
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function configureTypeOptions($typeOptions)
    {
        $typeOptions['choices'] = $this->options['choices'];
        if ($this->options['choice_list']) {
            $typeOptions['choice_list'] = $this->options['choice_list'];
        }
        $typeOptions['multiple'] = $this->options['multiple'];

        return $typeOptions;
    }


    /**
     * {@inheritDoc}
     */
    public function addField(FormBuilder $formBuilder)
    {
        $formBuilder->add($this->property, 'choice', $this->typeOptions);

        return $formBuilder;
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
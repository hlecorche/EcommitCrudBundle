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

class FieldFilterBoolean extends AbstractFieldFilter
{
    /**
     * {@inheritDoc}
     */
    protected function configureOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'value_true' => 1,
                'value_false' => 0,
                'not_null_is_true' => false,
                'null_is_false' => true,
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function configureTypeOptions($typeOptions)
    {
        $typeOptions['multiple'] = false;
        $typeOptions['choices'] = self::getChoices();

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
        if (empty($value) || !is_scalar($value)) {
            return $queryBuilder;
        }

        if ($value == 'T') {
            $parameterName = 'value_true' . str_replace(' ', '', $this->property);
            if ($this->options['not_null_is_true']) {
                $parameterNameFalse = 'value_false' . str_replace(' ', '', $this->property);
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
                $queryBuilder->andWhere(sprintf('%s = :%s',$aliasSearch, $parameterName))
                    ->setParameter($parameterName, $this->options['value_true']);
            }

            return $queryBuilder;
        } elseif ($value == 'F') {
            $parameterName = 'value_false' . str_replace(' ', '', $this->property);
            if (is_null($this->options['value_false'])) {
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
        } else {
            return $queryBuilder;
        }
    }

    public static function getChoices()
    {
        return array(
            'T' => 'filter.true',
            'F' => 'filter.false',
        );
    }
}

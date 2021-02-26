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
use Ecommit\JavascriptBundle\Form\Type\JqueryDatePickerType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FieldFilterDate extends AbstractFieldFilter
{
    const GREATER_THAN = '>';
    const GREATER_EQUAL = '>=';
    const SMALLER_THAN = '<';
    const SMALLER_EQUAL = '<=';
    const EQUAL = '=';

    /**
     * {@inheritDoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'type' => JqueryDatePickerType::class,
                'with_time' => function (Options $options) {
                    if (DateTimeType::class === $options['type']) {
                        return true;
                    }

                    return false;
                },
            )
        );

        $resolver->setRequired(
            array(
                'comparator',
            )
        );

        $resolver->setAllowedValues(
            'comparator',
            array(
                self::EQUAL,
                self::GREATER_EQUAL,
                self::GREATER_THAN,
                self::SMALLER_EQUAL,
                self::SMALLER_THAN,
            )
        );

        $resolver->setAllowedValues(
            'type',
            array(
                DateType::class,
                DateTimeType::class,
                JqueryDatePickerType::class,
            )
        );
    }

    protected function configureTypeOptions($typeOptions)
    {
        $typeOptions['input'] = 'datetime';

        if (JqueryDatePickerType::class === $this->options['type'] && $this->options['with_time'] && empty($this->typeOptions['time_format'])) {
            $typeOptions['time_format'] = 'H:i:s';
        }

        return $typeOptions;
    }

    /**
     * {@inheritDoc}
     */
    public function addField(FormBuilder $formBuilder)
    {
        $formBuilder->add($this->property, $this->options['type'], $this->typeOptions);

        return $formBuilder;
    }

    /**
     * {@inheritDoc}
     */
    public function changeQuery($queryBuilder, AbstractFormSearcher $formData, $aliasSearch)
    {
        $value = $formData->get($this->property);
        if (!empty($value) && $value instanceof \DateTime) {
            $parameterName = 'value_date_' . str_replace(' ', '', $this->property);

            switch ($this->options['comparator']):
                case FieldFilterDate::SMALLER_THAN:
                case FieldFilterDate::GREATER_EQUAL:
                    if (!$this->options['with_time']) {
                        $value->setTime(0, 0, 0);
                    }
                    $value = $value->format('Y-m-d H:i:s');
                    $queryBuilder->andWhere(
                        sprintf('%s %s :%s', $aliasSearch, $this->options['comparator'], $parameterName)
                    )
                        ->setParameter($parameterName, $value);
                    break;
                case FieldFilterDate::SMALLER_EQUAL:
                case FieldFilterDate::GREATER_THAN:
                    if (!$this->options['with_time']) {
                        $value->setTime(23, 59, 59);
                    }
                    $value = $value->format('Y-m-d H:i:s');
                    $queryBuilder->andWhere(
                        sprintf('%s %s :%s', $aliasSearch, $this->options['comparator'], $parameterName)
                    )
                        ->setParameter($parameterName, $value);
                    break;
                default:
                    $valueDateInf = clone $value;
                    $valueDateSup = clone $value;
                    if (!$this->options['with_time']) {
                        $valueDateInf->setTime(0, 0, 0);
                        $valueDateSup->setTime(23, 59, 59);
                    }
                    $valueDateInf = $valueDateInf->format('Y-m-d H:i:s');
                    $valueDateSup = $valueDateSup->format('Y-m-d H:i:s');
                    $parameterNameInf = 'value_date_inf_' . str_replace(' ', '', $this->property);
                    $parameterNameSup = 'value_date_sup_' . str_replace(' ', '', $this->property);
                    $queryBuilder->andWhere(
                        sprintf(
                            '%s >= :%s AND %s <= :%s',
                            $aliasSearch,
                            $parameterNameInf,
                            $aliasSearch,
                            $parameterNameSup
                        )
                    )
                        ->setParameter($parameterNameInf, $valueDateInf)
                        ->setParameter($parameterNameSup, $valueDateSup);
                    break;
            endswitch;
        }

        return $queryBuilder;
    }
}

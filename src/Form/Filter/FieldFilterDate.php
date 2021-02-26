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
use Ecommit\JavascriptBundle\Form\Type\JqueryDatePickerType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FieldFilterDate extends AbstractFieldFilter
{
    public const GREATER_THAN = '>';
    public const GREATER_EQUAL = '>=';
    public const SMALLER_THAN = '<';
    public const SMALLER_EQUAL = '<=';
    public const EQUAL = '=';

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'type' => JqueryDatePickerType::class,
                'with_time' => function (Options $options) {
                    if (DateTimeType::class === $options['type']) {
                        return true;
                    }

                    return false;
                },
            ]
        );

        $resolver->setRequired(
            [
                'comparator',
            ]
        );

        $resolver->setAllowedValues(
            'comparator',
            [
                self::EQUAL,
                self::GREATER_EQUAL,
                self::GREATER_THAN,
                self::SMALLER_EQUAL,
                self::SMALLER_THAN,
            ]
        );

        $resolver->setAllowedValues(
            'type',
            [
                DateType::class,
                DateTimeType::class,
                JqueryDatePickerType::class,
            ]
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
     * {@inheritdoc}
     */
    public function addField(FormBuilder $formBuilder)
    {
        $formBuilder->add($this->property, $this->options['type'], $this->typeOptions);

        return $formBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function changeQuery($queryBuilder, AbstractFormSearcher $formData, $aliasSearch)
    {
        $value = $formData->get($this->property);
        if (!empty($value) && $value instanceof \DateTime) {
            $parameterName = 'value_date_'.str_replace(' ', '', $this->property);

            switch ($this->options['comparator']) {
                case self::SMALLER_THAN:
                case self::GREATER_EQUAL:
                    if (!$this->options['with_time']) {
                        $value->setTime(0, 0, 0);
                    }
                    $value = $value->format('Y-m-d H:i:s');
                    $queryBuilder->andWhere(
                        sprintf('%s %s :%s', $aliasSearch, $this->options['comparator'], $parameterName)
                    )
                        ->setParameter($parameterName, $value);
                    break;
                case self::SMALLER_EQUAL:
                case self::GREATER_THAN:
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
                    $parameterNameInf = 'value_date_inf_'.str_replace(' ', '', $this->property);
                    $parameterNameSup = 'value_date_sup_'.str_replace(' ', '', $this->property);
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
            }
        }

        return $queryBuilder;
    }
}

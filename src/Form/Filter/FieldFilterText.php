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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class FieldFilterText extends AbstractFieldFilter
{
    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'must_begin' => false,
                'must_end' => false,
                'min_length' => null,
                'max_length' => 255,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function addField(FormBuilder $formBuilder)
    {
        $formBuilder->add($this->property, TextType::class, $this->typeOptions);

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
                    'min' => $this->options['min_length'],
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
        $parameterName = 'value_text_'.str_replace(' ', '', $this->property);
        if (null === $value || '' === $value || !is_scalar($value)) {
            return $queryBuilder;
        }

        if ($this->options['must_begin'] && $this->options['must_end']) {
            $queryBuilder->andWhere(sprintf('%s = :%s', $aliasSearch, $parameterName))
                ->setParameter($parameterName, $value);
        } else {
            $after = ($this->options['must_begin']) ? '' : '%';
            $before = ($this->options['must_end']) ? '' : '%';
            $value = addcslashes($value, '%_');
            $like = $after.$value.$before;
            $queryBuilder->andWhere(
                $queryBuilder->expr()->like($aliasSearch, ':'.$parameterName)
            )
                ->setParameter($parameterName, $like);
        }

        return $queryBuilder;
    }
}

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

use Ecommit\CrudBundle\Crud\SearchFormBuilder;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class TextFilter extends AbstractFilter
{
    protected const LIKE_ESCAPE_CHAR = '!';

    public function buildForm(SearchFormBuilder $builder, string $property, array $options): void
    {
        $typeOptions = $this->getTypeOptions($options, [
            'constraints' => [
                new Assert\Length(
                    min: $options['min_length'],
                    max: $options['max_length'],
                ),
            ],
        ]);
        $builder->addField($property, $options['type'], $typeOptions);
    }

    public function updateQueryBuilder(mixed $queryBuilder, string $property, mixed $value, array $options): void
    {
        if (null === $value || '' === $value || !\is_string($value) || !mb_check_encoding($value, 'UTF-8')) {
            return;
        }

        $parameterName = 'value_text_'.str_replace(' ', '', $property);

        if ($options['must_begin'] && $options['must_end']) {
            $queryBuilder->andWhere(\sprintf('%s = :%s', $options['alias_search'], $parameterName))
                ->setParameter($parameterName, $value);
        } else {
            $after = (true === $options['must_begin']) ? '' : '%';
            $before = (true === $options['must_end']) ? '' : '%';
            $like = $after.$this->escapeLike($queryBuilder, $value).$before;
            $queryBuilder->andWhere(\sprintf("%s LIKE :%s ESCAPE '%s'", $options['alias_search'], $parameterName, self::LIKE_ESCAPE_CHAR))
                ->setParameter($parameterName, $like);
        }
    }

    protected function escapeLike(mixed $queryBuilder, string $value): string
    {
        if ($queryBuilder instanceof \Doctrine\ORM\QueryBuilder) {
            return $queryBuilder->getEntityManager()->getConnection()->getDatabasePlatform()->escapeStringForLike($value, self::LIKE_ESCAPE_CHAR);
        }

        // The DBAL QueryBuilder does not expose its connection: platform-specific wildcards (e.g. "[" on SQL Server) cannot be escaped
        return strtr($value, [
            self::LIKE_ESCAPE_CHAR => self::LIKE_ESCAPE_CHAR.self::LIKE_ESCAPE_CHAR,
            '%' => self::LIKE_ESCAPE_CHAR.'%',
            '_' => self::LIKE_ESCAPE_CHAR.'_',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'must_begin' => false,
            'must_end' => false,
            'min_length' => null,
            'max_length' => 255,
            'type' => TextType::class,
        ]);
    }
}

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

use Doctrine\Common\Persistence\ManagerRegistry;
use Ecommit\JavascriptBundle\Form\Type\EntityNormalizerTrait;
use Symfony\Bridge\Doctrine\Form\ChoiceList\ORMQueryBuilderLoader;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;

class FieldFilterEntity extends FieldFilterChoice implements FieldFilterDoctrineInterface
{
    use EntityNormalizerTrait;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * {@inheritDoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(
            array(
                'property' => null, // deprecated since 2.2, use "choice_label"
                'choice_label' => function (Options $options) {
                    // BC with the "property" option
                    if ($options['property']) {
                        trigger_error('The "property" option is deprecated since version 2.2. Use "choice_label" instead.', E_USER_DEPRECATED);

                        return $options['property'];
                    }

                    return null;
                },
                'em' => null,
                'query_builder' => null,
                'identifier' => null,
            )
        );

        $resolver->setRequired(
            array(
                'class',
            )
        );

        $resolver->setNormalizer('em', $this->getEmNormalizer($this->registry));
        $resolver->setNormalizer('query_builder', $this->getQueryBuilderNormalizer());
        $resolver->setNormalizer('identifier', $this->getIdentifierNormalizer());
    }

    protected function configureTypeOptions($typeOptions)
    {
        $typeOptions = parent::configureTypeOptions($typeOptions);

        $queryBuilderLoader = new ORMQueryBuilderLoader($this->options['query_builder']);

        $accessor = PropertyAccess::createPropertyAccessor();
        $choices = array();
        foreach ($queryBuilderLoader->getEntities() as $entity) {
            $id = $accessor->getValue($entity, $this->options['identifier']);
            $choices[$this->extractLabel($entity)] = $id;
        }

        $typeOptions['choices'] = $choices;
        if (!isset($typeOptions['placeholder']) && !$typeOptions['required']) {
            $typeOptions['placeholder'] = 'filter.choices.placeholder';
        }

        return $typeOptions;
    }

    /**
     * Extract property that should be used for displaying the entities as text in the HTML element
     * @param object $object
     * @throws \Exception
     */
    protected function extractLabel($object)
    {
        if ($this->options['choice_label']) {
            $accessor = PropertyAccess::createPropertyAccessor();

            return $accessor->getValue($object, $this->options['choice_label']);
        } elseif (method_exists($object, '__toString')) {
            return (string)$object;
        } else {
            throw new \Exception('"choice_label" option or "__toString" method must be defined"');
        }
    }

    public function getRegistry()
    {
        return $this->registry;
    }

    public function setRegistry(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }
}

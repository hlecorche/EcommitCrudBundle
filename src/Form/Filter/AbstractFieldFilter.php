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
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

abstract class AbstractFieldFilter
{
    protected $columnId;
    protected $property;
    protected $options;
    protected $typeOptions;
    protected $isInitiated = false;

    /**
     * @param string $columnId    Column id
     * @param string $property    Field Name (search form)
     * @param array  $options     Options
     * @param array  $typeOptions Type options
     */
    public function __construct($columnId, $property, $options = [], $typeOptions = [])
    {
        $this->columnId = $columnId;
        $this->property = $property;

        $this->options = $options;
        if (!isset($typeOptions['required'])) {
            $typeOptions['required'] = false;
        }
        $this->typeOptions = $typeOptions;
    }

    public function init(): void
    {
        if ($this->isInitiated) {
            return;
        }

        // Define options
        $resolver = new OptionsResolver();
        $this->configureCommonOptions($resolver);
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($this->options);

        // Define type options
        $this->typeOptions = $this->configureTypeOptions($this->typeOptions);

        $this->isInitiated = true;
    }

    protected function configureCommonOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'validate' => true,
            ]
        );
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
    }

    /**
     * @param array $typeOptions
     *
     * @return array
     */
    protected function configureTypeOptions($typeOptions)
    {
        return $typeOptions;
    }

    /**
     * Adds the field into the form.
     *
     * @return FormBuilder
     */
    abstract public function addField(FormBuilder $formBuilder);

    /**
     * Changes the query.
     *
     * @param QueryBuilder $queryBuilder
     * @param string       $aliasSearch
     *
     * @return QueryBuilder
     */
    abstract public function changeQuery($queryBuilder, AbstractFormSearcher $formData, $aliasSearch);

    /**
     * Add auto validation.
     *
     * @param $value
     */
    public function autoValidate(AbstractFormSearcher $value, ExecutionContextInterface $context): void
    {
        if (!$this->options['validate']) {
            return;
        }
        $autoConstraints = $this->getAutoConstraints();
        if (0 == \count($autoConstraints)) {
            return;
        }
        $context->getValidator()
            ->inContext($context)
            ->atPath($this->getProperty())
            ->validate($value->get($this->getProperty()), $autoConstraints);
    }

    /**
     * Gets auto constraints list.
     *
     * @return array
     */
    protected function getAutoConstraints()
    {
        return [];
    }

    /**
     * Returns the column id associated at this object.
     *
     * @return string
     */
    public function getColumnId()
    {
        return $this->columnId;
    }

    /**
     * Returns the property associated at this object.
     *
     * @return string
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * @param string $label
     * @param bool   $displayLabelInErrors
     */
    public function setLabel($label, $displayLabelInErrors = false): void
    {
        if (!empty($label) && !isset($this->typeOptions['label'])) {
            $this->typeOptions['label'] = $label;
        }
        if (!isset($this->typeOptions['label_attr']['data-display-in-errors']) && $displayLabelInErrors) {
            $this->typeOptions['label_attr']['data-display-in-errors'] = '1';
        }
    }
}

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
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

abstract class AbstractFieldFilter
{
    protected $columnId;
    protected $property;
    protected $options;
    protected $typeOptions;
    protected $isInitiated = false;

    /**
     * @param string $columnId Column id
     * @param string $property Field Name (search form)
     * @param array $options Options
     * @param array $typeOptions Type options
     */
    public function __construct($columnId, $property, $options = array(), $typeOptions = array())
    {
        $this->columnId = $columnId;
        $this->property = $property;

        $this->options = $options;
        if (!isset($typeOptions['required'])) {
            $typeOptions['required'] = false;
        }
        $this->typeOptions = $typeOptions;
    }

    public function init()
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

    /**
     * @param OptionsResolverInterface $resolver
     */
    protected function configureCommonOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'validate' => true,
            )
        );
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    protected function configureOptions(OptionsResolverInterface $resolver)
    {
    }

    /**
     * @param array $typeOptions
     * @return array
     */
    protected function configureTypeOptions($typeOptions)
    {
        return $typeOptions;
    }

    /**
     * Adds the field into the form
     * @param FormBuilder $formBuilder
     * @return FormBuilder
     */
    abstract public function addField(FormBuilder $formBuilder);

    /**
     * Changes the query
     * @param QueryBuilder $queryBuilder
     * @param AbstractFormSearcher $formData
     * @param string $aliasSearch
     * @return QueryBuilder
     */
    abstract public function changeQuery($queryBuilder, AbstractFormSearcher $formData, $aliasSearch);

    /**
     * Add auto validation
     * @param $value
     * @param ExecutionContextInterface $context
     */
    public function autoValidate(AbstractFormSearcher $value, ExecutionContextInterface $context)
    {
        if (!$this->options['validate']) {
            return;
        }
        $autoConstraints = $this->getAutoConstraints();
        if (count($autoConstraints) == 0) {
            return;
        }
        $context->getValidator()
            ->inContext($context)
            ->atPath($this->getProperty())
            ->validate($value->get($this->getProperty()), $autoConstraints);
    }

    /**
     * Gets auto constraints list
     * @return array
     */
    protected function getAutoConstraints()
    {
        return array();
    }

    /**
     * Returns the column id associated at this object
     *
     * @return string
     */
    public function getColumnId()
    {
        return $this->columnId;
    }

    /**
     * Returns the property associated at this object
     *
     * @return string
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        if (!empty($label) && !isset($this->typeOptions['label'])) {
            $this->typeOptions['label'] = $label;
        }
        if (!isset($this->typeOptions['label_attr']['data-display-in-errors'])) {
            $this->typeOptions['label_attr']['data-display-in-errors'] = '1';
        }
    }
}

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

abstract class AbstractFieldFilter
{
    protected $columnId;
    protected $property;
    protected $options;
    protected $typeOptions;
    protected $label;
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
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($this->options);

        // Define type options
        $this->typeOptions = $this->configureTypeOptions($this->typeOptions);

        $this->isInitiated = true;
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
     * Returns the column id associated at this object
     *
     * @return string
     */
    public function getColumnId()
    {
        return $this->columnId;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        if (!empty($label) && !isset($this->typeOptions['label'])) {
            $this->typeOptions['label'] = $label;
        }
    }
}
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

namespace Ecommit\CrudBundle\Form\Searcher;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

abstract class AbstractFormSearcher
{
    protected $fieldFilters;

    /**
     * @var PropertyAccessor
     */
    protected $accessor;

    protected $commonOptions = [];

    public $isSubmitted = false;

    /**
     * Declares fields.
     *
     * @return array
     */
    public function configureFieldsFilter()
    {
        return [];
    }

    /**
     * Gets field value.
     *
     * @param string $field Field Name
     *
     * @return mixed
     */
    public function get($field)
    {
        try {
            $value = $this->getAccessor()->getValue($this, $field);
        } catch (AccessException $e) {
            $value = null;
        }

        return $value;
    }

    /**
     * Clears this objet
     * Used before storing this object in session
     * By default, If one property is not public and it doesn't begin
     * by "field", it will be deleted.
     */
    public function clear(): void
    {
        unset($this->fieldFilters);
        unset($this->accessor);
        $this->clearExceptFields();
    }

    /**
     * Sub method of "clear" method
     * Remove properties if they are not fields
     * If one property is not public and it doesn't begin
     * by "field", it will be deleted.
     */
    protected function clearExceptFields(): void
    {
        foreach ($this as $key => $value) {
            $variable = new \ReflectionProperty($this, $key);
            if (!$variable->isPublic() && !preg_match('/^field/', $key)) {
                unset($this->$key);
            }
        }
    }

    /**
     * Returns fields.
     *
     * @return array
     */
    public function getFieldsFilter($registry = null)
    {
        if (!$this->fieldFilters) {
            $this->fieldFilters = [];
            foreach ($this->configureFieldsFilter() as $field) {
                $this->fieldFilters[] = $field;
                if (!empty($registry) && $field instanceof \Ecommit\CrudBundle\Form\Filter\FieldFilterDoctrineInterface) {
                    $field->setRegistry($registry);
                }
                $field->init();
            }
        }

        return $this->fieldFilters;
    }

    /**
     * Sets fields.
     */
    public function setFieldsFilter(array $filters): void
    {
        $this->fieldFilters = $filters;
    }

    /**
     * Changes the form (global change).
     *
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    public function globalBuildForm(FormBuilderInterface $formBuilder)
    {
        return $formBuilder;
    }

    /**
     * Changes the query (global change).
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return QueryBuilder
     */
    public function globalChangeQuery($queryBuilder)
    {
        return $queryBuilder;
    }

    /**
     * Returns true if auto validation is enabled.
     *
     * @return bool
     */
    public function automaticValidationIsEnabled()
    {
        return true;
    }

    /**
     * Returns true if labels are displayed in errors messages.
     *
     * @return bool
     */
    public function displayLabelInErrors()
    {
        return false;
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addConstraint(new Callback('validateFormSearcher'));
    }

    public static function validateFormSearcher($value, ExecutionContextInterface $context): void
    {
        if ($value->automaticValidationIsEnabled()) {
            foreach ($value->getFieldsFilter() as $field) {
                $field->autoValidate($value, $context);
            }
        }
    }

    /**
     * @return PropertyAccessor
     */
    protected function getAccessor()
    {
        if (!isset($this->accessor) || !$this->accessor) {
            $this->accessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->accessor;
    }

    /**
     * @return array
     */
    public function getCommonOptions()
    {
        return $this->commonOptions;
    }

    /**
     * @param array $commonOptions
     */
    public function setCommonOptions($commonOptions): void
    {
        $this->commonOptions = $commonOptions;
    }
}

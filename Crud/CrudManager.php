<?php

/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\Crud;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Ecommit\CrudBundle\Controller\CrudAbstractController;
use Ecommit\CrudBundle\Paginator\DoctrinePaginator;
use Ecommit\CrudBundle\Form\Filter\FilterTypeAbstract;
use Ecommit\CrudBundle\Form\Type\FormSearchType;

class CrudManager
{
    const DESC = 'DESC';
    const ASC = 'ASC';
    
    protected $session_name;
    protected $session_values;
    
    protected $available_columns = array();
    protected $available_number_results_displayed = array();
    protected $default_sort = null;
    protected $default_sense = null;
    protected $default_number_results_displayed = null;
    protected $form_filter_values_object = null;
    protected $form_filter = null;
    protected $query_builder = null;
    protected $container = null;
    protected $paginator = null;
    protected $build_paginator = true;
    protected $route_name = null;
    protected $route_params = array();
    protected $search_route_name = null;
    protected $search_route_params = array();
    protected $div_id_search = 'crud_search';
    protected $div_id_list = 'crud_list';
    
    /**
     * Constructor
     * 
     * @param string $session_name   Session name
     * @param CrudAbstractController $controller
     * @return CrudManager 
     */
    public function __construct($session_name, CrudAbstractController $controller)
    {
        if(empty($session_name))
        {
            throw new \Exception('Variable session_name is required');
        }
        $this->session_name = $session_name;
        $this->container = $controller->getContainer();
        $this->session_values = new CrudSessionManager();
        return $this;
    }
    
    /**
     * Add a column inside the crud
     * 
     * @param string $id   Column id (used everywhere inside the crud)
     * @param string $alias   Column SQL alias
     * @param string $label   Column label (used in the header table)
     * @param bool $sortable   If the column is sortable
     * @param bool $default_displayed   If the column is displayed, by default
     * @param type $alias_search   Column SQL alias, used during searchs. If null, $alias is used.
     * @return CrudManager 
     */
    public function addColumn($id, $alias, $label, $sortable = true, $default_displayed = true, $alias_search = null)
    {
        $column = new CrudColumn($id, $alias, $label, $sortable, $default_displayed, $alias_search);
        $this->available_columns[$id] = $column;
        return $this;
    }
    
    /**
     * Sets the query builder
     * 
     * @param QueryBuilder $query_builder
     * @return CrudManager 
     */
    public function setQueryBuilder(QueryBuilder $query_builder)
    {
        $this->query_builder = $query_builder;
        return $this;
    }
    
    /**
     * Gets the query builder
     * 
     * @return QueryBuilder 
     */
    public function getQueryBuilder()
    {
        return $this->query_builder;
    }
    
    /**
     * Returns available numbers of results displayed
     * 
     * @return array 
     */
    public function getAvailableNumberResultsDisplayed()
    {
        return $this->available_number_results_displayed;
    }
    
    /**
     * Sets available numbers of results displayed
     * 
     * @param array $available_number_results_displayed
     * @param int $default_value
     * @return CrudManager
     */
    public function setAvailableNumberResultsDisplayed(Array $available_number_results_displayed, $default_value)
    {
        $this->available_number_results_displayed = $available_number_results_displayed;
        $this->default_number_results_displayed = $default_value;
        return $this;
    }
    
    /**
     * Set the default sort
     * 
     * @param string $sort   Column id
     * @param const $sense   Sense (CrudManager::ASC / CrudManager::DESC)
     * @return CrudManager 
     */
    public function setDefaultSort($sort, $sense)
    {
        $this->default_sort = $sort;
        $this->default_sense = $sense;
        return $this;
    }
    
    /**
     * Sets the list route
     * 
     * @param string $route_name
     * @param array $parameters 
     * @return CrudManager
     */
    public function setRoute($route_name, $parameters = array())
    {
        $this->route_name = $route_name;
        $this->route_params = $parameters;
        return $this;
    }
    
    /**
     * Returns the route name
     * 
     * @return string
     */
    public function getRouteName()
    {
        return $this->route_name;
    }
    
    /**
     * Returns the route params
     * 
     * @return array
     */
    public function getRouteParams()
    {
        return $this->route_params;
    }
    
    /**
     * Returns the list url
     * 
     * @param array $parameters   Additional parameters
     * @return string
     */
    public function getUrl($parameters = array())
    {
        $parameters = \array_merge($this->route_params, $parameters);
        return $this->container->get('router')->generate($this->route_name, $parameters);
    }
    
    /**
     * Sets the search route
     * 
     * @param string $route_name
     * @param array $parameters
     * @return CrudManager
     */
    public function setSearchRoute($route_name, $parameters = array())
    {
        $this->search_route_name = $route_name;
        $this->search_route_params = $parameters;
        return $this;
    }
    
    /**
     * Returns the search url
     * 
     * @param array $parameters   Additional parameters
     * @return string
     */
    public function getSearchUrl($parameters = array())
    {
        $parameters = \array_merge($this->search_route_params, $parameters);
        return $this->container->get('router')->generate($this->search_route_name, $parameters);
    }
    
    /**
     * Enables (or not) the auto build paginator
     * 
     * @param bool $value 
     */
    public function setBuildPaginator($value)
    {
        $this->build_paginator = $value;
    }
    
    /**
     * Sets the paginator
     * 
     * @param Object $value 
     */
    public function setPaginator($value)
    {
        $this->paginator = $value;
    }
    
    /**
     * Adds search form
     * 
     * @param FilterTypeAbstract $form_filter_values_object
     * @return CrudManager 
     */
    public function createFilterForm(FilterTypeAbstract $form_filter_values_object)
    {
        $this->form_filter_values_object = $form_filter_values_object;
                
        $form_builder = $this->container->get('form.factory')->createNamedBuilder('crud_search', new FormSearchType());
        foreach($form_filter_values_object->getFieldsFilter() as $field)
        {
            if(!($field instanceof \Ecommit\CrudBundle\Form\Filter\FormFilterAbstract ))
            {
                throw new \Exception('Crud: FilterType: getFieldsFilter() must only returns FormFilterAbstract implementations');
            }
            $form_builder = $field->addField($form_builder);
        }
        $this->form_filter = $form_builder->getForm();
        return $this;
    }
    
    /**
     * Process search form
     * 
     */
    public function processForm()
    {
        if(empty($this->form_filter_values_object))
        {
            throw new NotFoundHttpException('Crud: Form filter does not exist');
        }
        
        $request = $this->container->get('request');
        if($request->query->has('raz'))
        {
            $this->raz();
            return;
        }
        if ($request->getMethod() == 'POST')
        {
            $this->form_filter->bindRequest($request);             
            if($this->form_filter->isValid())
            {
                $this->changeFilterValues($this->form_filter->getData());
                $this->save();
            }
        }
    }
    
    /**
     * Inits the CRUD
     * 
     */
    public function init()
    {
        //Cheks not empty values
        $check_values = array('available_columns', 'available_number_results_displayed', 'default_sort',
            'default_sense', 'default_number_results_displayed', 'query_builder', 'route_name');
        if(!empty($this->form_filter_values_object))
        {
            $check_values[] = 'search_route_name';
        }
        foreach($check_values as $value)
        {
            if(empty($this->$value))
            {
                throw new \Exception('Config Crud: Option '.$value.' is required');
            }
        }
        
        //Loads user values inside this object
        $this->load();
        
        //Process request (npp, sort, sense, change_columns)
        $this->processRequest();
        
        //Filter form: Allocates object (Defaults values and validators)
         $request = $this->container->get('request');
        if(!empty($this->form_filter_values_object) && !$request->query->has('raz'))
        {
            //IMPORTANT
            //We have not to allocate directelly the "$this->session_values->form_filter_values_object" object
            //because otherwise it will be linked to form, and will be updated when the "bind" function will
            //be called (If form is not valid, the session values will still be updated: Undesirable behavior)
            $values = clone $this->session_values->form_filter_values_object;
            $this->form_filter->setData($values);
        }
        
        //Saves
        $this->save();
    }
    
    /**
     * Builds the query
     * 
     */
    public function buildQuery()
    {
        //Builds query
        $column_sort_id = $this->session_values->sort;
        $column_sort_alias = $this->available_columns[$column_sort_id]->alias;
        $this->query_builder->orderBy($column_sort_alias, $this->session_values->sense);
        
        //Adds form filter filters
        if(!empty($this->form_filter_values_object))
        {
            foreach($this->form_filter_values_object->getFieldsFilter() as $field)
            {
                if(!isset($this->available_columns[$field->getColumnId()]))
                {
                    throw new \Exception('Crud: FilterType: getFieldsFilter(): Column id does not exit: '.$field->getColumnId());
                }
                $column = $this->available_columns[$field->getColumnId()];
                $this->query_builder = $field->changeQuery($this->query_builder, 
                        $this->session_values->form_filter_values_object, $column);
            }
        }
        
        
        //Builds paginator
        if($this->build_paginator)
        {
            $request = $this->container->get('request');
            $page = $request->query->get('page', 1);

            $this->paginator = new DoctrinePaginator($this->session_values->number_results_displayed);
            $this->paginator->setQueryBuilder($this->query_builder);
            $this->paginator->setPage($page);
            $this->paginator->init();
        }
    }
    
    /**
     * Load user values
     * 
     */
    protected function load()
    {
        $session = $this->container->get('request')->getSession();
        $object = $session->get($this->session_name);
        if(empty($object))
        {
            $this->session_values->columns_diplayed = $this->getDefaultDisplayedColumns();
            $this->session_values->number_results_displayed = $this->default_number_results_displayed;
            $this->session_values->sense = $this->default_sense;
            $this->session_values->sort = $this->default_sort;
            if(!empty($this->form_filter_values_object))
            {
                $this->session_values->form_filter_values_object = clone $this->form_filter_values_object;
            }
        }
        else
        {
            $this->session_values = $object;
            $this->checkCrudSessionManager();
        }
    }
    
    /**
     * Saves user value
     * 
     */
    protected function save()
    {
        $session = $this->container->get('request')->getSession();
        if(is_object($this->session_values->form_filter_values_object))
        {
            $this->session_values->form_filter_values_object->clear();
        }
        $session->set($this->session_name, $this->session_values);
    }
    
    /**
     * Reset search form values
     * 
     */
    protected function raz()
    {
        $new_value = clone $this->form_filter_values_object;
        $this->changeFilterValues($new_value);
        $this->save();
    }

    /**
     * Process request
     * 
     */
    protected function processRequest()
    {
        $request = $this->container->get('request');
        if($request->request->has('crud_display_config'))
        {
            $display_config = $request->request->get('crud_display_config');
            if(isset($display_config['displayed_columns']))
            {
                $this->changeColumnsDisplayed($display_config['displayed_columns']);
            }
            if(isset($display_config['npp']))
            {
                $this->changeNumberResultsDisplayed($display_config['npp']);
            }
        }
        if($request->query->has('sort'))
        {
            $this->changeSort($request->query->get('sort'));
        }
        if($request->query->has('sense'))
        {
            $this->changeSense($request->query->get('sense'));
        }
    }
    
    /**
     * Return default displayed columns
     * 
     * @return array
     */
    protected function getDefaultDisplayedColumns()
    {
        $columns = array();
        foreach($this->available_columns as $column)
        {
            if($column->default_displayed)
            {
                $columns[] = $column->id;
            }
        }
        if(count($columns) == 0)
        {
            throw new \Exception('Config Crud: One column displayed is required');
        }
        return $columns;
    }
    
    
    /**
     * Checks user values
     */
    protected function checkCrudSessionManager()
    {
       //Forces change => checks
        $this->changeNumberResultsDisplayed($this->session_values->number_results_displayed);
        $this->changeColumnsDisplayed($this->session_values->columns_diplayed);
        $this->changeSort($this->session_values->sort);
        $this->changeSense($this->session_values->sense);
        $this->changeFilterValues($this->session_values->form_filter_values_object);
    }
    
    /**
     * User Action: Changes number of displayed results
     * 
     * @param int $value
     */
    protected function changeNumberResultsDisplayed($value)
    {
        if(in_array($value, $this->available_number_results_displayed))
        {
             $this->session_values->number_results_displayed = $value;
        }
        else
        {
            $this->session_values->number_results_displayed = $this->default_number_results_displayed;
        }
    }
    
    /**
     * User Action: Changes displayed columns
     * 
     * @param array $value   (columns id)
     */
    protected function changeColumnsDisplayed($value)
    {
        if(!is_array($value))
        {
            $this->getDefaultDisplayedColumns();
        }
        $new_displayed_columns = array();
        $available_columns = $this->available_columns;
        foreach($value as $column_name)
        {
            if(array_key_exists($column_name, $available_columns))
            {
                $new_displayed_columns[] = $column_name;
            }
        }
        if(count($new_displayed_columns) == 0)
        {
            $new_displayed_columns = $this->getDefaultDisplayedColumns();
        }
        $this->session_values->columns_diplayed = $new_displayed_columns;
    }
    
    /**
     * User Action: Changes sort
     *
     * @param string $value   Column id
     */
    protected function changeSort($value)
    {
        $available_columns = $this->available_columns;
        if(array_key_exists($value, $available_columns) && $available_columns[$value]->sortable)
        {
            $this->session_values->sort = $value;
        }
        else
        {
            $this->session_values->sort = $this->default_sort;
        }
    }
    
    /**
     * User action: Changes sense
     * 
     * @param const $value   Sens (ASC / DESC)
     */
    protected function changeSense($value)
    {
        if($value == self::ASC || $value == self::DESC)
        {
            $this->session_values->sense = $value;
        }
        else
        {
            $this->session_values->sense = $this->default_sense;
        }
    }
    
    /**
     * User action: Changes search form values
     * 
     * @param Object $value
     */
    protected function changeFilterValues($value)
    {
        if(empty($this->form_filter_values_object))
        {
            return;
        }
        if(\get_class($value) == \get_class($this->form_filter_values_object))
        {
            $this->session_values->form_filter_values_object = $value;
        }
        else
        {
            $this->session_values->form_filter_values_object = clone $this->form_filter_values_object;
        }
    }
    
    /**
     * Clears this object, before sending it to template
     * 
     */
    public function clearTemplate()
    {
        $this->query_builder = null;
        if(empty($this->form_filter_values_object))
        {
            $this->form_filter = null;
        }
        else
        {
            $this->form_filter = $this->form_filter->createView();
        }
        $this->form_filter_values_object = null;
    }
    
    /**
     * Returns availabled columns
     * 
     * @return array
     */
    public function getColumns()
    {
        return $this->available_columns;
    }
    
    /**
     * Returns one column
     * 
     * @return CrudColumn   Column id
     */
    public function getColumn($column_id)
    {
        if(isset($this->available_columns[$column_id]))
        {
            return $this->available_columns[$column_id];
        }
        throw new \Exception('Crud: Column '.$column_id.' does not exist');
    }
    
    /**
     * Returns user values
     * 
     * @return CrudSessionManager 
     */
    public function getSessionValues()
    {
        return $this->session_values;
    }
    
    /**
     * Returns the paginator
     * @return Object 
     */
    public function getPaginator()
    {
        return $this->paginator;
    }
    
    /**
     * Returns the search form
     * 
     * @return Form (before clearTemplate) or FormView (after clearTemplate)
     */
    public function getFilterForm()
    {
        return $this->form_filter;
    }
    
    /**
     * Returns the div id search
     * 
     * @return string
     */
    public function getDivIdSearch()
    {
        return $this->div_id_search;
    }
    
    /**
     * Sets the div id search
     * 
     * @param string
     * @return CrudManager
     */
    public function setDivIdSearch($div_id_search)
    {
        $this->div_id_search = $div_id_search;
        return $this;
    }
    
    /**
     * Returns the div id list
     * 
     * @return string
     */
    public function getDivIdList()
    {
        return $this->div_id_list;
    }
    
    /**
     * Sets the div id list
     * 
     * @param string
     * @return CrudManager
     */
    public function setDivIdList($div_id_list)
    {
        $this->div_id_list = $div_id_list;
        return $this;
    }
}
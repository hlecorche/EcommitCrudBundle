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

use Ecommit\CrudBundle\Controller\CrudAbstractController;
use Ecommit\CrudBundle\Entity\UserCrudSettings;
use Ecommit\CrudBundle\Form\Searcher\FormSearcherAbstract;
use Ecommit\CrudBundle\Form\Type\FormSearchType;
use Ecommit\CrudBundle\Paginator\DbalPaginator;
use Ecommit\CrudBundle\Paginator\DoctrinePaginator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CrudManager
{
    const DESC = 'DESC';
    const ASC = 'ASC';
    
    protected $session_name;
    protected $session_values;
    
    protected $available_columns = array();
    protected $available_virtual_columns = array();
    protected $available_number_results_displayed = array();
    protected $default_sort = null;
    protected $default_sense = null;
    protected $default_number_results_displayed = null;
    protected $form_searcher_values_object = null;
    protected $form_searcher = null;
    protected $query_builder = null;
    protected $use_dbal = false;
    protected $persistent_settings = false;
    protected $update_database = false;
    protected $paginator = null;
    protected $build_paginator = true;
    protected $route_name = null;
    protected $route_params = array();
    protected $search_route_name = null;
    protected $search_route_params = array();
    protected $div_id_search = 'crud_search';
    protected $div_id_list = 'crud_list';
    
    /*
     * Services
     */
    protected $router;
    protected $form_factory;
    protected $request;
    protected $doctrine;
    protected $user;

    /**
     * Constructor
     * 
     * @param string $session_name   Session name
     * @param CrudAbstractController $controller
     * @return CrudManager 
     */
    public function __construct($session_name, CrudAbstractController $controller)
    {
        if(!\preg_match('/^[a-zA-Z0-9_]{1,30}$/', $session_name))
        {
            throw new \Exception('Variable session_name is not given or is invalid');
        }
        $this->session_name = $session_name;
        $container = $controller->getContainer();
        $this->router = $container->get('router');
        $this->form_factory = $container->get('form.factory');
        $this->request = $container->get('request');
        $this->doctrine = $container->get('doctrine');
        $this->user = $controller->getUser();
        $this->session_values = new CrudSessionManager();
        return $this;
    }
    
    /**
     * Add a column inside the crud
     * 
     * @param string $id   Column id (used everywhere inside the crud)
     * @param string $alias   Column SQL alias
     * @param string $label   Column label (used in the header table)
     * @param array  $options   Options:
     *      * sortable: If the column is sortable (Default: true)
     *      * default_displayed: If the column is displayed, by default (Default: true)
     *      * alias_search: Column SQL alias, used during searchs. If null, $alias is used.
     *      * alias_sort: Column(s) SQL alias (string or array of strings), used during sorting. If null, $alias is used.
     * @return CrudManager 
     */
    public function addColumn($id, $alias, $label, $options = array())
    {
        if(\strlen($id) > 30)
        {
            throw new \Exception('Column id is too long');
        }
        
        $default_options = array(
            'sortable' => true,
            'default_displayed' => true,
            'alias_search' => null,
            'alias_sort' => null,
        );
        $options = \array_merge($default_options, $options);
        $column = new CrudColumn($id, $alias, $label, $options['sortable'], $options['default_displayed'], $options['alias_search'], $options['alias_sort']);
        $this->available_columns[$id] = $column;
        return $this;
    }
    
    /**
     * Add a virtual column inside the crud
     * 
     * @param string $id   Column id (used everywhere inside the crud)
     * @param string $alias_search   Column SQL alias, used during searchs.
     * @return CrudManager 
     */
    public function addVirtualColumn($id, $alias_search)
    {
        $column = new CrudColumn($id, $alias_search, null, false, false, null, null);
        $this->available_virtual_columns[$id] = $column;
        return $this;
    }
    
    /**
     * Sets the query builder
     * 
     * @param QueryBuilder $query_builder
     * @return CrudManager 
     */
    public function setQueryBuilder($query_builder)
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
        return $this->router->generate($this->route_name, $parameters);
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
        return $this->router->generate($this->search_route_name, $parameters);
    }
    
    /**
     * Enables (or not) the auto build paginator
     * 
     * @param bool|closure $value 
     */
    public function setBuildPaginator($value)
    {
        $this->build_paginator = $value;
        return $this;
    }
    
    /*
     * Use (or not) DBAL
     * 
     * @param bool $value
     */
    public function setUseDbal($value)
    {
        $this->use_dbal = $value;
        return $this;
    }
    
    /*
     * Use (or not) persistent settings
     * 
     * @param bool $value
     */
    public function setPersistentSettings($value)
    {
        $this->persistent_settings = $value;
        return $this;
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
     * @param FormSearcherAbstract $form_searcher_values_object
     * @return CrudManager 
     */
    public function createSearcherForm(FormSearcherAbstract $form_searcher_values_object)
    {
        $this->form_searcher_values_object = $form_searcher_values_object;
                
        $form_name = sprintf('crud_search_%s', $this->session_name);
        $form_builder = $this->form_factory->createNamedBuilder($form_name, new FormSearchType());
        foreach($form_searcher_values_object->getFieldsFilter($this->doctrine) as $field)
        {
            if(!($field instanceof \Ecommit\CrudBundle\Form\Filter\FieldFilterAbstract ))
            {
                throw new \Exception('Crud: FormSearcherAbstract: getFieldsFilter() must only returns FieldFilterAbstract implementations');
            }
            $form_builder = $field->addField($form_builder);
        }
        //Global 
        $form_builder = $form_searcher_values_object->globalBuildForm($form_builder);
        $this->form_searcher = $form_builder->getForm();
        return $this;
    }
    
    /**
     * Process search form
     * 
     */
    public function processForm()
    {
        if(empty($this->form_searcher_values_object))
        {
            throw new NotFoundHttpException('Crud: Form searcher does not exist');
        }
        
        if($this->request->query->has('raz'))
        {
            return;
        }
        if ($this->request->getMethod() == 'POST')
        {
            $this->form_searcher->handleRequest($this->request);             
            if($this->form_searcher->isValid())
            {
                $this->changeFilterValues($this->form_searcher->getData());
                $this->changePage(1);
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
        if(!empty($this->form_searcher_values_object))
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
        
        //Searcher form: Allocates object (Defaults values and validators)
        if(!empty($this->form_searcher_values_object) && !$this->request->query->has('raz'))
        {
            //IMPORTANT
            //We have not to allocate directelly the "$this->session_values->form_searcher_values_object" object
            //because otherwise it will be linked to form, and will be updated when the "bind" function will
            //be called (If form is not valid, the session values will still be updated: Undesirable behavior)
            $values = clone $this->session_values->form_searcher_values_object;
            $this->form_searcher->setData($values);
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
        $column_sort_alias = $this->available_columns[$column_sort_id]->alias_sort;
        if(empty($column_sort_alias))
        {
            //Sort alias is not defined. Alias is used
            $column_sort_alias = $this->available_columns[$column_sort_id]->alias;
            $this->query_builder->orderBy($column_sort_alias, $this->session_values->sense);
        }
        elseif(is_array($column_sort_alias))
        {
            //Sort alias is defined in many columns
            foreach($column_sort_alias as $one_column_sort_alias)
            {
                $this->query_builder->addOrderBy($one_column_sort_alias, $this->session_values->sense);
            }
        }
        else
        {
            //Sort alias is defined in one column
            $this->query_builder->orderBy($column_sort_alias, $this->session_values->sense);
        }
        
        //Adds form searcher filters
        if(!empty($this->form_searcher_values_object))
        {
            foreach($this->form_searcher_values_object->getFieldsFilter() as $field)
            {
                if(isset($this->available_columns[$field->getColumnId()]))
                {
                    $column = $this->available_columns[$field->getColumnId()];
                }
                elseif(isset($this->available_virtual_columns[$field->getColumnId()]))
                {
                    $column = $this->available_virtual_columns[$field->getColumnId()];
                }
                else
                {
                    throw new \Exception('Crud: FormSearcherAbstract: getFieldsFilter(): Column id does not exit: '.$field->getColumnId());
                }
                $this->query_builder = $field->changeQuery($this->query_builder, 
                        $this->session_values->form_searcher_values_object, $column);
            }
            
            //Global change Query
            $this->query_builder = $this->session_values->form_searcher_values_object->globalChangeQuery($this->query_builder);
        }
        
        
        //Builds paginator
        if(is_object($this->build_paginator) && $this->build_paginator instanceof \Closure)
        {
            //Case: Manual paginator (by closure) is enabled
            $this->paginator = $this->build_paginator->__invoke(
                    $this->query_builder,
                    $this->session_values->page,
                    $this->session_values->number_results_displayed
            );
        }
        elseif($this->build_paginator)
        {
            //Case: Auto paginator is enabled
            $page = $this->session_values->page;
            
            if($this->use_dbal)
            {
                $this->paginator = new DbalPaginator($this->session_values->number_results_displayed);
                $this->paginator->setDbalQueryBuilder($this->query_builder);
            }
            else
            {
                $this->paginator = new DoctrinePaginator($this->session_values->number_results_displayed);
                $this->paginator->setQueryBuilder($this->query_builder);
            }
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
        $session = $this->request->getSession();
        $object = $session->get($this->session_name); //Load from session
        
        if(!empty($object))
        {
            $this->session_values = $object;
            $this->checkCrudSessionManager();
            return;
        }
        
        //If session is null => Retrieve from database
        //Only if persistent settings is enabled
        if($this->persistent_settings)
        {
            $object_database = $this->doctrine->getRepository('EcommitCrudBundle:UserCrudSettings')->findOneBy(array(
                'user' => $this->user,
                'crud_name' => $this->session_name
            ));
            if($object_database)
            {
                $this->session_values = $object_database->transformToCrudSessionManager(new CrudSessionManager());
                if(!empty($this->form_searcher_values_object))
                {
                    $this->session_values->form_searcher_values_object = clone $this->form_searcher_values_object;
                }
                $this->checkCrudSessionManager();
                return;
            }
        }
        
        //Session and database values are null: Default values;
        $this->session_values->columns_diplayed = $this->getDefaultDisplayedColumns();
        $this->session_values->number_results_displayed = $this->default_number_results_displayed;
        $this->session_values->sense = $this->default_sense;
        $this->session_values->sort = $this->default_sort;
        if(!empty($this->form_searcher_values_object))
        {
            $this->session_values->form_searcher_values_object = clone $this->form_searcher_values_object;
        }
    }
    
    /**
     * Saves user value
     * 
     */
    protected function save()
    {
        //Save in session
        $session = $this->request->getSession();
        if(is_object($this->session_values->form_searcher_values_object))
        {
            $this->session_values->form_searcher_values_object->clear();
        }
        $session->set($this->session_name, $this->session_values);
        
        //Save in database
        if($this->persistent_settings && $this->update_database)
        {
            $object_database = $this->doctrine->getRepository('EcommitCrudBundle:UserCrudSettings')->findOneBy(array(
                'user' => $this->user,
                'crud_name' => $this->session_name
            ));
            $em = $this->doctrine->getManager();
            
            if($object_database)
            {
                //Update object in database
                $object_database->updateFromSessionManager($this->session_values);
                $em->flush();
            }
            else
            {
                //Create object in database only if not default values
                if($this->session_values->columns_diplayed != $this->getDefaultDisplayedColumns() ||
                        $this->session_values->number_results_displayed != $this->default_number_results_displayed ||
                        $this->session_values->sense != $this->default_sense ||
                        $this->session_values->sort != $this->default_sort)
                {
                    $object_database = new UserCrudSettings();
                    $object_database->setUser($this->user);
                    $object_database->setCrudName($this->session_name);
                    $object_database->updateFromSessionManager($this->session_values);
                    $em->persist($object_database);
                    $em->flush();
                }
            }
        }
    }
    
    /**
     * Reset search form values
     * 
     */
    public function raz()
    {
        if ($this->form_searcher_values_object) {
            $new_value = clone $this->form_searcher_values_object;
            $this->changeFilterValues($new_value);
            $this->form_searcher->setData(clone $new_value);
        }
        $this->changePage(1);
        $this->save();
    }
    
    /**
     * Reset display settings
     * 
     */
    protected function razDisplaySettings()
    {
        $this->session_values->columns_diplayed = $this->getDefaultDisplayedColumns();
        $this->session_values->number_results_displayed = $this->default_number_results_displayed;
        $this->session_values->sense = $this->default_sense;
        $this->session_values->sort = $this->default_sort;
        
        if($this->persistent_settings)
        {
            //Remove settings in database
            $qb = $this->doctrine->getManager()->createQueryBuilder();
            $qb->delete('EcommitCrudBundle:UserCrudSettings', 's')
                    ->andWhere('s.user = :user AND s.crud_name = :crud_name')
                    ->setParameters(array('user' => $this->user, 'crud_name' => $this->session_name))
                    ->getQuery()
                    ->execute();
        }
    }

    /**
     * Process request
     * 
     */
    protected function processRequest()
    {
        if($this->request->query->has('razsettings'))
        {
            //Reset display settings
            $this->razDisplaySettings();
            return;
        }
        if($this->request->query->has('raz'))
        {
            $this->raz();
            return;
        }
        $display_config_form_name = sprintf('crud_display_config_%s', $this->session_name);
        if($this->request->request->has($display_config_form_name))
        {
            $display_config = $this->request->request->get($display_config_form_name);
            if(isset($display_config['displayed_columns']))
            {
                $this->changeColumnsDisplayed($display_config['displayed_columns']);
            }
            if(isset($display_config['npp']))
            {
                $this->changeNumberResultsDisplayed($display_config['npp']);
            }
        }
        if($this->request->query->has('sort'))
        {
            $this->changeSort($this->request->query->get('sort'));
        }
        if($this->request->query->has('sense'))
        {
            $this->changeSense($this->request->query->get('sense'));
        }
        if($this->request->query->has('page'))
        {
            $this->changePage($this->request->query->get('page'));
        }
    }
    
    /**
     * Return default displayed columns
     * 
     * @return array
     */
    public function getDefaultDisplayedColumns()
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
     * Return default number of results
     * @return int
     */
    public function getDefaultNumberResultsDisplayed()
    {
        return $this->default_number_results_displayed;
    }

    protected function testIfDatabaseMustMeUpdated($old_value, $new_value)
    {
        if($old_value != $new_value)
        {
            $this->update_database = true;
        }
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
        $this->changeFilterValues($this->session_values->form_searcher_values_object);
        $this->changePage($this->session_values->page);
    }
    
    /**
     * User Action: Changes number of displayed results
     * 
     * @param int $value
     */
    protected function changeNumberResultsDisplayed($value)
    {
        $old_value = $this->session_values->number_results_displayed;
        if(in_array($value, $this->available_number_results_displayed))
        {
             $this->session_values->number_results_displayed = $value;
        }
        else
        {
            $this->session_values->number_results_displayed = $this->default_number_results_displayed;
        }
        $this->testIfDatabaseMustMeUpdated($old_value, $value);
    }
    
    /**
     * User Action: Changes displayed columns
     * 
     * @param array $value   (columns id)
     */
    protected function changeColumnsDisplayed($value)
    {
        $old_value = $this->session_values->columns_diplayed;
        if(!is_array($value))
        {
            $value = $this->getDefaultDisplayedColumns();
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
        $this->testIfDatabaseMustMeUpdated($old_value, $new_displayed_columns);
    }
    
    /**
     * User Action: Changes sort
     *
     * @param string $value   Column id
     */
    protected function changeSort($value)
    {
        $old_value = $this->session_values->sort;
        $available_columns = $this->available_columns;
        if(array_key_exists($value, $available_columns) && $available_columns[$value]->sortable)
        {
            $this->session_values->sort = $value;
            $this->testIfDatabaseMustMeUpdated($old_value, $value);
        }
        else
        {
            $this->session_values->sort = $this->default_sort;
            $this->testIfDatabaseMustMeUpdated($old_value, $this->default_sort);
        }
    }
    
    /**
     * User action: Changes sense
     * 
     * @param const $value   Sens (ASC / DESC)
     */
    protected function changeSense($value)
    {
        $old_value = $this->session_values->sense;
        if($value == self::ASC || $value == self::DESC)
        {
            $this->session_values->sense = $value;
            $this->testIfDatabaseMustMeUpdated($old_value, $value);
        }
        else
        {
            $this->session_values->sense = $this->default_sense;
            $this->testIfDatabaseMustMeUpdated($old_value, $this->default_sense);
        }
    }
    
    /**
     * User action: Changes page number
     * 
     * @param string $value   Page number
     */
    protected function changePage($value)
    {
        $value = \intval($value);
        if($value > 1000000000000)
        {
            $value = 1;
        }
        $this->session_values->page = $value;
    }
    
    /**
     * User action: Changes search form values
     * 
     * @param Object $value
     */
    protected function changeFilterValues($value)
    {
        if(empty($this->form_searcher_values_object))
        {
            return;
        }
        if(\get_class($value) == \get_class($this->form_searcher_values_object))
        {
            $this->session_values->form_searcher_values_object = $value;
        }
        else
        {
            $this->session_values->form_searcher_values_object = clone $this->form_searcher_values_object;
        }
    }
    
    /**
     * Clears this object, before sending it to template
     * 
     */
    public function clearTemplate()
    {
        $this->query_builder = null;
        $this->form_factory = null;
        $this->request = null;
        $this->doctrine = null;
        if(empty($this->form_searcher_values_object))
        {
            $this->form_searcher = null;
        }
        else
        {
            $this->form_searcher = $this->form_searcher->createView();
        }
        $this->form_searcher_values_object = null;
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
    public function getSearcherForm()
    {
        return $this->form_searcher;
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
    
    /**
     * Gets session name
     * 
     * @return string
     */
    public function getSessionName()
    {
        return $this->session_name;
    }
}
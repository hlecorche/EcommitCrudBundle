<?php

/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\Helper\Twig;

use Ecommit\CrudBundle\Crud\Crud;
use Ecommit\CrudBundle\Helper\CrudHelper;
use Ecommit\CrudBundle\Paginator\AbstractPaginator;
use Twig_Environment;
use Twig_Extension;
use Twig_Function_Method;

class CrudExtension extends Twig_Extension
{
    protected $crud_helper;
    protected $templating;
    
    
    /**
     * Constructor
     * 
     * @param CrudHelper $crud_helper 
     */
    public function __construct(CrudHelper $crud_helper, Twig_Environment $templating)
    {
        $this->crud_helper = $crud_helper;
        $this->templating = $templating;
    }
    
    /**
    * Returns the name of the extension.
    *
    * @return string The extension name
    */
    public function getName()
    {
        return 'ecommit_crud';
    }
    
    /**
    * Returns a list of global functions to add to the existing list.
    *
    * @return array An array of global functions
    */
    public function getFunctions()
    {
        return array(
            'paginator_links' => new Twig_Function_Method($this, 'paginator_links', array('is_safe' => array('all'))),
            'crud_paginator_links' => new Twig_Function_Method($this, 'crud_paginator_links', array('is_safe' => array('all'))),
            'crud_th' => new Twig_Function_Method($this, 'th', array('is_safe' => array('all'))),
            'crud_td' => new Twig_Function_Method($this, 'td', array('is_safe' => array('all'))),
            'crud_display_config' => new Twig_Function_Method($this, 'display_config', array('is_safe' => array('all'))),
            'crud_search_form' => new Twig_Function_Method($this, 'search_form', array('is_safe' => array('all'))),
            'crud_search_reset' => new Twig_Function_Method($this, 'search_reset', array('is_safe' => array('all'))),
            'crud_declare_modal' => new Twig_Function_Method($this, 'declare_modal', array('is_safe' => array('all'))),
            'crud_remote_modal' => new Twig_Function_Method($this, 'remote_modal', array('is_safe' => array('all'))),
            'crud_form_modal' => new Twig_Function_Method($this, 'form_modal', array('is_safe' => array('all'))),
            'display_first_error' => new Twig_Function_Method($this, 'display_first_error', array('is_safe' => array('all'))),
        );
    }
    
    /**
     * Twig function: "paginator_links"
     *  
     * @see CrudHelper:paginatorLinks
     */
    public function paginator_links(AbstractPaginator $paginator, $route_name, $route_params = array(), $options = array())
    {
        return $this->crud_helper->paginatorLinks($paginator, $route_name, $route_params, $options);
    }
    
    /**
     * Twig function: "crud_paginator_links"
     *  
     * @see CrudHelper:crudPaginatorLinks
     */
    public function crud_paginator_links(Crud $crud, $options = array(), $ajax_options = array())
    {
        return $this->crud_helper->crudPaginatorLinks($crud, $options, $ajax_options);
    }
    
    /**
     * Twig function: "crud_th"
     *  
     * @see CrudHelper:th
     */
    public function th($column_id, Crud $crud, $options = array(), $th_options = array(), $ajax_options = array())
    {
        return $this->crud_helper->th($column_id, $crud, $options, $th_options, $ajax_options);
    }
    
    /**
     * Twig function: "crud_td"
     *  
     * @see CrudHelper:td
     */
    public function td($column_id, Crud $crud, $value, $escape = true, $td_options = array())
    {
        return $this->crud_helper->td($column_id, $crud, $value, $escape, $td_options);
    }
    
    /**
     * Twig function: "crud_display_config"
     * 
     * @param Crud $crud
     * @param array $options   Options :
     *        * modal:  Include (or not) inside a modal. Default: true
     *        * image_url: Url image (button)
     * @param array $ajax_options   Ajax options
     * @return string 
     */
    public function display_config(Crud $crud, $options = array(), $ajax_options = array())
    {
        $default_options = array('modal' => true,
                                 'image_url' => 'ecr/images/i16/list.png',
                                );
        $options = \array_merge($default_options, $options);
        if(!isset($ajax_options['update']))
        {
            $ajax_options['update'] = $crud->getDivIdList();
        }
        
        $form = $this->crud_helper->getFormDisplayConfig($crud);
        $template_name = $options['modal']? 'EcommitCrudBundle:Crud:form_config_modal.html.twig' : 'EcommitCrudBundle:Crud:form_config_nomodal.html.twig';
        return $this->templating->render($template_name, 
                array('form' => $form,
                    'url' => $crud->getUrl(),
                    'reset_settings_url' => $crud->getUrl(array('razsettings' => 1)),
                    'ajax_options' => $ajax_options,
                    'image_url' => $options['image_url'],
                    'suffix' => $crud->getSessionName(),
                    'default_npp' => $crud->getDefaultResultsPerPage(),
                    'default_columns' => $crud->getDefaultDisplayedColumns(),
                    'use_bootstrap' => $this->crud_helper->useBootstrap(),
                 ));
    }
    
    /**
     * Twig function: "crud_search_form"
     * 
     * @see CrudHelper:searchFormTag 
     */
    public function search_form(Crud $crud, $ajax_options = array(), $html_options = array())
    {
        return $this->crud_helper->searchFormTag($crud, $ajax_options, $html_options);
    }
    
    /**
     * Twig function: "crud_search_reset"
     * 
     * @see CrudHelper:searchResetButton 
     */
    public function search_reset(Crud $crud, $options = array(), $ajax_options = array(), $html_options = array())
    {
        return $this->crud_helper->searchResetButton($crud, $options, $ajax_options, $html_options);
    }
    
    /**
     * Twig function: "crud_declare_modal"
     * 
     * @see CrudHelper:reclareModal 
     */
    public function declare_modal($modal_id)
    {
        return $this->crud_helper->declareModal($modal_id);
    }
    
    /**
     * Twig function: "crud_remote_modal"
     * 
     * @see CrudHelper:remoteModal 
     */
    public function remote_modal($modal_id, $url, $js_on_close = null, $ajax_options = array())
    {
        return $this->crud_helper->remoteModal($modal_id, $url, $js_on_close, $ajax_options);
    }
    
    /**
     * Twig function: "crud_form_modal"
     * 
     * @see CrudHelper:formModal 
     */
    public function form_modal($modal_id, $url, $ajax_options = array(), $html_options = array())
    {
        return $this->crud_helper->formModal($modal_id, $url, $ajax_options, $html_options);
    }
    
    /**
     * Twig function: "display_first_error"
     * Auto-displays first error
     * 
     * @return string 
     */
    public function display_first_error()
    {
        $js = $this->templating->render('EcommitCrudBundle:Form:display_first_error.html.twig');
        return \preg_replace("/\n|\t|\r/",'', $js);
    }
}
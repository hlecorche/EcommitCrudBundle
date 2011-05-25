<?php

/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) Hubert LECORCHE <hlecorche@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\Helper\Twig;

use Twig_Extension;
use Twig_Function_Method;
use Ecommit\CrudBundle\Helper\CrudHelper;
use Ecommit\CrudBundle\Crud\CrudManager;
use Twig_Environment;

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
            'crud_paginator_links' => new Twig_Function_Method($this, 'paginator_links', array('is_safe' => array('all'))),
            'crud_th' => new Twig_Function_Method($this, 'th', array('is_safe' => array('all'))),
            'crud_td' => new Twig_Function_Method($this, 'td', array('is_safe' => array('all'))),
            'crud_display_config' => new Twig_Function_Method($this, 'display_config', array('is_safe' => array('all'))),
            'crud_search_form' => new Twig_Function_Method($this, 'search_form', array('is_safe' => array('all'))),
            'crud_search_reset' => new Twig_Function_Method($this, 'search_reset', array('is_safe' => array('all'))),
        );
    }
    
    /**
     * Twig function: "crud_paginator"
     *  
     * @see CrudHelper:paginator
     */
    public function paginator_links(CrudManager $crud, $ajax_options = array(), $max_pages_before = 3, $max_pages_after = 3, $image_first = '/bundles/ecommitcrud/images/i16/resultset_first.png', $image_last = '/bundles/ecommitcrud/images/i16/resultset_last.png', $image_previous = '/bundles/ecommitcrud/images/i16/resultset_previous.png', $image_next = '/bundles/ecommitcrud/images/i16/resultset_next.png', $attribute_page = '?page=')
    {
        return $this->crud_helper->paginatorLinks($crud, $ajax_options, $max_pages_before, $max_pages_after, $image_first, $image_last, $image_previous, $image_next, $attribute_page);
    }
    
    /**
     * Twig function: "crud_th"
     *  
     * @see CrudHelper:th
     */
    public function th($column_id, CrudManager $crud, $th_options = array(), $ajax_options = array(), $label = null, $image_up = '/bundles/ecommitcrud/images/i16/sort_incr.png', $image_down = '/bundles/ecommitcrud/images/i16/sort_decrease.png', $attribute_page = '?')
    {
        return $this->crud_helper->th($column_id, $crud, $th_options, $ajax_options, $label, $image_up, $image_down, $attribute_page);
    }
    
    /**
     * Twig function: "crud_td"
     *  
     * @see CrudHelper:td
     */
    public function td($column_id, CrudManager $crud, $value, $escape = true, $td_options = array())
    {
        return $this->crud_helper->td($column_id, $crud, $value, $escape, $td_options);
    }
    
    /**
     * Twig function: "crud_display_config"
     * 
     * @param CrudManager $crud
     * @param array $ajax_options   Ajax options
     * @param string $image_url   Url image (button)
     * @return string 
     */
    public function display_config(CrudManager $crud, $ajax_options = array(), $image_url = '/bundles/ecommitcrud/images/i16/list.png')
    {
        if(!isset($ajax_options['update']))
        {
            $ajax_options['update'] = 'crud_list';
        }
        
        $form = $this->crud_helper->getFormDisplayConfig($crud);
        return $this->templating->render('EcommitCrudBundle:Crud:form_config.html.twig', 
                array('form' => $form, 'url' => $crud->getUrl(), 'ajax_options' => $ajax_options, 'image_url' => $image_url));
    }
    
    /**
     * Twig function: "crud_search_form"
     * 
     * @see CrudHelper:searchFormTag 
     */
    public function search_form(CrudManager $crud, $ajax_options = array(), $html_options = array())
    {
        return $this->crud_helper->searchFormTag($crud, $ajax_options, $html_options);
    }
    
    /**
     * Twig function: "crud_search_reset"
     * 
     * @see CrudHelper:searchResetButton 
     */
    public function search_reset(CrudManager $crud, $label = 'Reset', $ajax_options = array(), $html_options = array(), $link_parameter = '?raz=1')
    {
        return $this->crud_helper->searchResetButton($crud, $label, $ajax_options, $html_options, $link_parameter);
    }
}
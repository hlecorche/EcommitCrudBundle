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
            'crud_declare_modal' => new Twig_Function_Method($this, 'declare_modal', array('is_safe' => array('all'))),
            'crud_remote_modal' => new Twig_Function_Method($this, 'remote_modal', array('is_safe' => array('all'))),
            'crud_form_modal' => new Twig_Function_Method($this, 'form_modal', array('is_safe' => array('all'))),
            'message_confirm' => new Twig_Function_Method($this, 'message_confirm', array('is_safe' => array('all'))),
            'message_error' => new Twig_Function_Method($this, 'message_error', array('is_safe' => array('all'))),
            'flash_confirm' => new Twig_Function_Method($this, 'flash_confirm', array('is_safe' => array('all'))),
            'flash_error' => new Twig_Function_Method($this, 'flash_error', array('is_safe' => array('all'))),
        );
    }
    
    /**
     * Twig function: "crud_paginator"
     *  
     * @see CrudHelper:paginator
     */
    public function paginator_links(CrudManager $crud, $ajax_options = array(), $max_pages_before = 3, $max_pages_after = 3, $image_first = 'ecr/images/i16/resultset_first.png', $image_last = 'ecr/images/i16/resultset_last.png', $image_previous = 'ecr/images/i16/resultset_previous.png', $image_next = 'ecr/images/i16/resultset_next.png', $attribute_page = '?page=')
    {
        return $this->crud_helper->paginatorLinks($crud, $ajax_options, $max_pages_before, $max_pages_after, $image_first, $image_last, $image_previous, $image_next, $attribute_page);
    }
    
    /**
     * Twig function: "crud_th"
     *  
     * @see CrudHelper:th
     */
    public function th($column_id, CrudManager $crud, $th_options = array(), $ajax_options = array(), $label = null, $image_up = 'ecr/images/i16/sort_incr.png', $image_down = 'ecr/images/i16/sort_decrease.png', $attribute_page = '?')
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
    public function display_config(CrudManager $crud, $ajax_options = array(), $image_url = 'ecr/images/i16/list.png')
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
     * Twig function: "message_confirm"
     * 
     * @see CrudHelper:message 
     */
    public function message_confirm($message, $close_label = 'Close', $width = '100%')
    {
        return $this->crud_helper->message($message, CrudHelper::MESSAGE_CONFIRM, $close_label, $width);
    }
    
    /**
     * Twig function: "message_error"
     * 
     * @see CrudHelper:message 
     */
    public function message_error($message, $close_label = 'Close', $width = '100%')
    {
        return $this->crud_helper->message($message, CrudHelper::MESSAGE_ERROR, $close_label, $width);
    }
    
    /**
     * Twig function: "flash_confirm"
     * 
     * @see CrudHelper:flashMessage 
     */
    public function flash_confirm($name, $close_label = 'Close', $width = '100%')
    {
        return $this->crud_helper->flashMessage($name, CrudHelper::MESSAGE_CONFIRM, $close_label, $width);
    }
    
    /**
     * Twig function: "flash_error"
     * 
     * @see CrudHelper:flashMessage 
     */
    public function flash_error($name, $close_label = 'Close', $width = '100%')
    {
        return $this->crud_helper->flashMessage($name, CrudHelper::MESSAGE_ERROR, $close_label, $width);
    }
}
<?php

/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\Helper;

use Ecommit\CrudBundle\Crud\CrudManager;
use Ecommit\CrudBundle\Form\Type\DisplayConfigType;
use Ecommit\CrudBundle\Paginator\AbstractPaginator;
use Ecommit\JavascriptBundle\jQuery\Manager;
use Ecommit\UtilBundle\Helper\UtilHelper;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Form\FormFactory;

class CrudHelper
{
    const MESSAGE_CONFIRM = 'message_confirm';
    const MESSAGE_ERROR = 'message_error';
    
    protected $util;
    protected $javascript_manager;
    protected $form_factory;
    protected $router;
    protected $translator;
    protected $use_bootstrap;
    
    /**
     * Constructor
     * 
     * @param UtilHelper $util
     * @param Manager $javascript_manager
     * @param FormFactory $form_factory
     * @param bool $use_boostrap
     */
    public function __construct(UtilHelper $util, Manager $javascript_manager, FormFactory $form_factory, Router $router, Translator $translator, $use_boostrap)
    {
        $this->util = $util;
        $this->javascript_manager = $javascript_manager;
        $this->form_factory = $form_factory;
        $this->router = $router;
        $this->translator = $translator;
        $this->use_bootstrap = $use_boostrap;
    }

    /**
     * @return bool
     */
    public function useBootstrap()
    {
        return $this->use_bootstrap;
    }
    
    /**
     * Returns links paginator
     * 
     * @param AbstractPaginator $paginator
     * @param string $route_name   Route name
     * @param array $route_params   Route parameters
     * @param array $options   Options:
     *        * ajax_options: Ajax Options. If null, Ajax is not used. Default: null
     *        * attribute_page: Attribute inside url. Default: page
     *        * type: Type of links paginator: elastic (all links) or sliding. Default: sliding
     *        * max_pages_before: Max links before current page (only if sliding type is used). Default: 3
     *        * max_pages_after: Max links after current page (only if sliding type is used). Default: 3
     *        * buttons: Type of buttons: text or image. Default: text
     *        * image_first:  Url image "<<" (only if image buttons is used)
     *        * image_previous:  Url image "<" (only if image buttons is used)
     *        * image_next:  Url image ">" (only if image buttons is used)
     *        * image_last:  Url image ">>" (only if image buttons is used)
     *        * text_first:  Text "<<" (only if text buttons is used)
     *        * text_previous:  Text "<" (only if text buttons is used)
     *        * text_next:  Text ">" (only if text buttons is used)
     *        * text_last:  Text ">>" (only if text buttons is used)
     * @return string
     */
    public function paginatorLinks(AbstractPaginator $paginator, $route_name, $route_params, $options)
    {
        $default_options = array('ajax_options' => null,
                                 'attribute_page' => 'page',
                                 'type' => 'sliding',
                                 'max_pages_before' => 3,
                                 'max_pages_after' => 3,
                                 'buttons' => 'text',
                                 'image_first' => '/ecr/images/i16/resultset_first.png',
                                 'image_previous' => '/ecr/images/i16/resultset_previous.png',
                                 'image_next' => '/ecr/images/i16/resultset_next.png',
                                 'image_last' => '/ecr/images/i16/resultset_last.png',
                                 'text_first' => '<<',
                                 'text_previous' => '<',
                                 'text_next' => '>',
                                 'text_last' => '>>',
                                );
        $options = \array_merge($default_options, $options);
        if(!\in_array($options['type'], array('sliding', 'elastic')))
        {
            throw new \Exception('Option sliding is not valid');
        }
        if(!\in_array($options['buttons'], array('text', 'image')))
        {
            throw new \Exception('Option sliding is not valid');
        }
        
        $navigation = '';
        if($paginator->haveToPaginate())
        {
            $navigation .= '<div class="pagination">';
            
            //First page / Previous page
            if ($paginator->getPage() != 1)
            {
                $navigation .= $this->elementPaginatorLinks(1, $options, 'first', $route_name, $route_params);
                $navigation .= $this->elementPaginatorLinks($paginator->getPreviousPage(), $options, 'previous', $route_name, $route_params);
            }
            
            //Pages before the current page
            $limit = ($options['type'] == 'sliding')? $paginator->getPage() - $options['max_pages_before'] : 1;
            for($page = $limit; $page < $paginator->getPage(); $page++)
            {
                if($page <= $paginator->getLastPage() && $page >= $paginator->getFirstPage())
                {
                    //The page exists, displays it
                    $navigation .= $this->elementPaginatorLinks($page, $options, 'page', $route_name, $route_params);
                }
            }
            
            //Current page
            $navigation .= $this->elementPaginatorLinks($paginator->getPage(), $options, 'page', $route_name, $route_params, true);
            
            //Pages after the current page
            $limit = ($options['type'] == 'sliding')? $paginator->getPage() + $options['max_pages_after'] : $paginator->getLastPage();
            for($page = $paginator->getPage() + 1; $page <= $limit; $page++)
            {
                if($page <= $paginator->getLastPage() && $page >= $paginator->getFirstPage())
                {
                    //The page exists, displays it
                    $navigation .= $this->elementPaginatorLinks($page, $options, 'page', $route_name, $route_params);
                }
            }
            
            //Next page / Last page
            if($paginator->getPage() != $paginator->getLastPage())
            {
                $navigation .= $this->elementPaginatorLinks($paginator->getNextPage(), $options, 'next', $route_name, $route_params);
                $navigation .= $this->elementPaginatorLinks($paginator->getLastPage(), $options, 'last', $route_name, $route_params);
            }
            
            $navigation .= '</div>';
        }
        return $navigation;
    }
    
    /**
     * Paginator links: Display one element
     * 
     * @param int $page  Page number
     * @param array $options   Options
     * @param string $element_name   first, previous, page, next or last
     * @param string $route_name
     * @param array $route_params
     * @param bool $actual   If element is the actual page
     */
    protected function elementPaginatorLinks($page, $options, $element_name, $route_name, $route_params, $actual = false)
    {
        $url = $this->router->generate($route_name, \array_merge($route_params, array($options['attribute_page'] => $page)));
        if($element_name == 'page')
        {
            if($actual)
            {
                $class = 'pagination_actual';
                $content = $page;
            }
            else
            {
                $class = 'pagination_no_actual';
                $content = $this->listePrivateLink($page, $url, array(), $options['ajax_options']);
            }            
        }
        else
        {
            $class = $options['buttons'].' '.$element_name;
            $button_name = $options['buttons'].'_'.$element_name;
            $button = $options[$button_name];
            if($options['buttons'] == 'text')
            {
                $content = $this->listePrivateLink($button, $url, array(), $options['ajax_options']);
            }
            else
            {
                $image = $this->util->tag('img', array('src' => $button, 'alt' => $element_name));
                $content = $this->listePrivateLink($image, $url, array(), $options['ajax_options']);
            }
        }
        
        return \sprintf('<span class="%s">%s</span>', $class, $content);
    }
    
    /**
     * Returns CRUD links paginator
     * 
     * @param CrudManager $crud
     * @param array $options   Options. See CrudHelper::paginatorLinks (ajax_options is ignored)
     * @param array $ajax_options   Ajax Options
     * @return string 
     */
    public function crudPaginatorLinks(CrudManager $crud, $options, $ajax_options)
    {
        if(!isset($ajax_options['update']))
        {
            $ajax_options['update'] = $crud->getDivIdList();
        }
        $options['ajax_options'] = $ajax_options;
        return $this->paginatorLinks($crud->getPaginator(), $crud->getRouteName(), $crud->getRouteParams(), $options);
    }
    
    /**
     * Returns one colunm, inside "header" CRUD
     * 
     * @param string $column_id   Column id
     * @param CrudManager $crud
     * @param array $options   Options :
     *        * label: Label. If null, default label is displayed
     *        * image_up: Url image "^"
     *        * image_down: Url image "V"
     * @param array $th_options   Html options
     * @param array $ajax_options   Ajax Options
     * @return string 
     */
    public function th($column_id, CrudManager $crud, $options, $th_options, $ajax_options)
    {
        $default_options = array('label' => null,
                                 'image_up' => '/ecr/images/i16/sort_incr.png',
                                 'image_down' => '/ecr/images/i16/sort_decrease.png',
                                );
        $options = \array_merge($default_options, $options);
        if(!isset($ajax_options['update']))
        {
            $ajax_options['update'] = $crud->getDivIdList();
        }
        $image_up = $options['image_up'];
        $image_down = $options['image_down'];

        //If the column is not to be shown, returns empty
        $session_values =  $crud->getSessionValues();
        if(!\in_array($column_id, $session_values->columns_diplayed))
        {
            return '';
        }
        
        //If the label was not defined, we take default label
        $column = $crud->getColumn($column_id);
        $label = $options['label'];
        if(\is_null($label))
        {
            $label = $column->label;
        }
        //I18N label
        $label = $this->translator->trans($label);
        //XSS protection
        $label = \htmlentities($label, ENT_QUOTES, 'UTF-8');
        
        //Case n°1: We cannot sort this column, we just show the label
        if(!$column->sortable)
        {
            return $this->util->tag('th', $th_options, $label);
        }
        
        //Case n°2: We can sort on this column, but the sorting is not active on her at present
        if($session_values->sort != $column_id)
        {
            $content = $this->listePrivateLink($label, $crud->getUrl(array('sort' => $column_id)), array(), $ajax_options);
            return $this->util->tag('th', $th_options, $content);
        }
        
        //Case n°3: We can sort on this column, and the sorting is active on her at present
        $image_src = ($session_values->sense == CrudManager::ASC)? $image_up : $image_down;
        $image_alt = ($session_values->sense == CrudManager::ASC)? 'V' : '^';
        $new_sense = ($session_values->sense == CrudManager::ASC)? CrudManager::DESC : CrudManager::ASC;
        $image = $this->util->tag('img', array('src' => $image_src, 'alt' => $image_alt));
        $link = $this->listePrivateLink($label, $crud->getUrl(array('sense' => $new_sense)), array(), $ajax_options);
        return $this->util->tag('th', $th_options, $link.$image);
    }
    
    /**
     * Returns one colunm, inside "body" CRUD
     * 
     * @param string $column_id   Column id
     * @param CrudManager $crud
     * @param string $value   Value
     * @param bool $escape   Escape (or not) the value
     * @param array $td_options   Html options
     * @return string 
     */
    public function td($column_id, CrudManager $crud, $value, $escape, $td_options)
    {
        //If the column is not to be shown, returns empty
        $session_values =  $crud->getSessionValues();
        if(!\in_array($column_id, $session_values->columns_diplayed))
        {
            return '';
        }
        
        //XSS protection
        if($escape)
        {
            $value = \htmlentities($value, ENT_QUOTES, 'UTF-8');
        }
        return $this->util->tag('td', $td_options, $value);
    }
    
    /**
     * Returns "Display Config" form
     * 
     * @param CrudManager $crud
     * @return FormView 
     */
    public function getFormDisplayConfig(CrudManager $crud)
    {
        $form_name = sprintf('crud_display_config_%s', $crud->getSessionName());
        $form = $this->form_factory->createNamed($form_name, new DisplayConfigType($crud));
        return $form->createView();
    }
    
    /**
     * Returns search form tag
     * 
     * @param CrudManager $crud
     * @param array $ajax_options   Ajax Options
     * @param type $html_options   Html options
     * @return string 
     */
    public function searchFormTag(CrudManager $crud, $ajax_options, $html_options)
    {
        if(!isset($ajax_options['update']))
        {
            $ajax_options['update'] = 'js_holder_for_multi_update_'.$crud->getSessionName();
        }
        return $this->javascript_manager->jQueryFormToRemote($crud->getSearchUrl(), $ajax_options, $html_options);
    }
    
    /**
     * Returns search reset button
     * 
     * @param CrudManager $crud
     * @param array $options   Options :
     *        * label: Label. Défault: Reset
     * @param array $ajax_options   Ajax options
     * @param array $html_options   Html options
     * @return string 
     */
    public function searchResetButton(CrudManager $crud, $options, $ajax_options, $html_options)
    {
        $default_options = array('label' => 'Reset',
                                );
        $options = \array_merge($default_options, $options);
        if(!isset($ajax_options['update']))
        {
            $ajax_options['update'] = 'js_holder_for_multi_update_'.$crud->getSessionName();
        }
        if(!isset($html_options['class']))
        {
            $html_options['class'] = ($this->use_bootstrap)? 'raz-bootstrap btn btn-default btn-sm' : 'raz';
        }
        $label = $this->translator->trans($options['label']);
        if($this->use_bootstrap)
        {
            $label = '<span class="glyphicon glyphicon-fire"></span> '.$label;
        }
        return $this->javascript_manager->jQueryButtonToRemote($label, $crud->getSearchUrl(array('raz' => 1)), $ajax_options, $html_options);
    }
    
    /**
     * Returns declaration of modal
     * 
     * @param string $modal_id   Modal id
     * @return string 
     */
    public function declareModal($modal_id)
    {
        $modal_id = str_replace(' ', '', $modal_id);
        return '<div id="'.$modal_id.'" class="crud_modal"><div class="contentWrap"></div></div>';
    }
    
    /**
     * Returns JS code to open modal window
     * 
     * @param string $modal_id   Modal id
     * @param string $url   Url
     * @param string $js_on_close   JS code excuted, during the closure of the modal 
     * @param array $ajax_options   Ajax options
     * @return string 
     */
    public function remoteModal($modal_id, $url, $js_on_close, $ajax_options)
    {
        $modal_id = str_replace(' ', '', $modal_id);
        //Create Callback (Opening window)
        $js_modal = "$('#$modal_id .contentWrap').html(data); ";
        $js_modal .= "var api_crud_modal = $('#$modal_id').overlay({oneInstance: false, api: true, fixed: false";
        $js_modal .= is_null($js_on_close)? '': " ,onClose: function() { $js_on_close }";
        $js_modal .= '}); ';
        $js_modal .= 'api_crud_modal.load();';

        //Add callback
        if(isset($ajax_options['success']))
        {
            $ajax_options['success'] = $js_modal.' '.$ajax_options['success'];
        }
        else
        {
            $ajax_options['success'] = $js_modal;
        }
        
        //Method
        if(!isset($ajax_options['method']))
        {
            $ajax_options['method'] = 'GET';
        }
        
        return $this->javascript_manager->jQueryRemoteFunction($url, $ajax_options);
    }
    
    /**
     * Returns modal form tag
     * 
     * @param string $modal_id   Modal id
     * @param string $url   Url
     * @param array $ajax_options   Ajax options
     * @param array $html_options   Html options
     * @return string 
     */
    public function formModal($modal_id, $url, $ajax_options, $html_options)
    {
        $modal_id = str_replace(' ', '', $modal_id);
        if(!isset($ajax_options['update']))
        {
            $ajax_options['update'] = $modal_id.' .contentWrap';
        }
        return $this->javascript_manager->jQueryFormToRemote($url, $ajax_options, $html_options);
    }
    
    /**
     * Creates a link
     * 
     * @param string $name   Name link
     * @param string $url   Url link
     * @param array $options_link_to   Html options
     * @param array $ajax_options   Ajax options (if null, Ajax is dissabled)
     * @return string 
     */
    protected function listePrivateLink($name, $url, $options_link_to = array(), $ajax_options = null)
    {
        if(is_null($ajax_options))
        {
                //No Ajax, Simple link
                $options_link_to['href'] = $url;
                return $this->util->tag('a', $options_link_to, $name);
        }
        else
        {
                //Ajax Request
                return $this->javascript_manager->jQueryLinkToRemote($name, $url, $ajax_options, $options_link_to);
        }
    }
}
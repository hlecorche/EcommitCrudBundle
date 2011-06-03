<?php

/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) Hubert LECORCHE <hlecorche@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\Helper;

use Ecommit\UtilBundle\Helper\UtilHelper;
use Ecommit\JavascriptBundle\jQuery\Manager;
use Ecommit\CrudBundle\Crud\CrudManager;
use Symfony\Component\Form\FormFactory;
use Ecommit\CrudBundle\Form\Type\DisplayConfigType;

class CrudHelper
{
    protected $util;
    protected $javascript_manager;
    protected $form_factory;
    
    /**
     * Constructor
     * 
     * @param UtilHelper $util
     * @param Manager $javascript_manager
     * @param FormFactory $form_factory 
     */
    public function __construct(UtilHelper $util, Manager $javascript_manager, FormFactory $form_factory)
    {
        $this->util = $util;
        $this->javascript_manager = $javascript_manager;
        $this->form_factory = $form_factory;
    }
    
    /**
     * Returns links paginator
     * 
     * @param CrudManager $crud
     * @param array $ajax_options   Ajax Options
     * @param int $max_pages_before   Number of displayed pages, before curent page
     * @param int $max_pages_after   Number of displayed pages, after curent page
     * @param string $image_first   Url image "<<"
     * @param string $image_last   Url image ">>"
     * @param string $image_previous   Url image "<"
     * @param sting $image_next   Url image ">"
     * @param sring $attribute_page   Text between the url page and the num page (ex. : "?page=")
     * @return string 
     */
    public function paginatorLinks(CrudManager $crud, $ajax_options, $max_pages_before, $max_pages_after, $image_first, $image_last, $image_previous, $image_next, $attribute_page)
    {
        if(!isset($ajax_options['update']))
        {
            $ajax_options['update'] = 'crud_list';
        }
        $paginator = $crud->getPaginator();
        $url = $crud->getUrl();
        $image_first = $this->util->getAssetUrl($image_first);
        $image_last = $this->util->getAssetUrl($image_last);
        $image_previous = $this->util->getAssetUrl($image_previous);
        $image_next = $this->util->getAssetUrl($image_next);
        
        $navigation = '';
	if ($paginator->haveToPaginate())
	{
            //First page / Previous page
            if ($paginator->getPage() != 1)
            {
                $navigation .= $this->listePrivateLink($this->util->tag('img', array('src' => $image_first, 'alt' => 'first', 'style' => 'vertical-align: top;')), $url.$attribute_page.'1', array(), $ajax_options);
                $navigation .= $this->listePrivateLink($this->util->tag('img', array('src' => $image_previous, 'alt' => 'previous', 'style' => 'vertical-align: top;')), $url.$attribute_page.$paginator->getPreviousPage(), array(), $ajax_options).' ';
            }
            
            //Links
            $links = array();
            
            //Pages before the current page
            for($page = $paginator->getPage() - $max_pages_before; $page < $paginator->getPage(); $page++)
            {
                if($page <= $paginator->getLastPage() && $page >= $paginator->getFirstPage())
                {
                    //The page exists, displays it
                    $links[] = $this->listePrivateLink($page, $url.$attribute_page.$page, array('class' => 'pagination_no_actual'), $ajax_options);
                }
            }
            
            //Current page
            $links[] = '<span class="pagination_actual">[ '.$paginator->getPage().' ]</span>';
            
            //Pages after the current page
            for($page = $paginator->getPage() + 1; $page <= $paginator->getPage() + $max_pages_after; $page++)
            {
                if($page <= $paginator->getLastPage() && $page >= $paginator->getFirstPage())
                {
                    //The page exists, displays it
                    $links[] = $this->listePrivateLink($page, $url.$attribute_page.$page, array('class' => 'pagination_no_actual'), $ajax_options);
                }
            }
            
            $navigation .= \join('&nbsp;&nbsp;', $links);
            
            //Next page / Last page
            if ($paginator->getPage() != $paginator->getLastPage())
	    {
                $navigation .= '&nbsp;&nbsp;'.$this->listePrivateLink($this->util->tag('img', array('src' => $image_next, 'alt' => 'next', 'style' => 'vertical-align: top;')), $url.$attribute_page.$paginator->getNextPage(), array(), $ajax_options);
                $navigation .= $this->listePrivateLink($this->util->tag('img', array('src' => $image_last, 'alt' => 'last', 'style' => 'vertical-align: top;')), $url.$attribute_page.$paginator->getLastPage(), array(), $ajax_options);
	    }
        }
        return $navigation;
    }
    
    /**
     * Returns one colunm, inside "header" CRUD
     * 
     * @param string $column_id   Column id
     * @param CrudManager $crud
     * @param array $th_options   Html options
     * @param array $ajax_options   Ajax Options
     * @param string $label   Label. If null, default label is displayed
     * @param string $image_up   Url image "^"
     * @param string $image_down   Url image "V"
     * @param string $attribute_page   Text between the url page and attributes (ex. : "?")
     * @return string 
     */
    public function th($column_id, CrudManager $crud, $th_options, $ajax_options, $label, $image_up, $image_down, $attribute_page)
    {
        if(!isset($ajax_options['update']))
        {
            $ajax_options['update'] = 'crud_list';
        }
        $image_up = $this->util->getAssetUrl($image_up);
        $image_down = $this->util->getAssetUrl($image_down);

        //If the column is not to be shown, returns empty
        $session_values =  $crud->getSessionValues();
        if(!\in_array($column_id, $session_values->columns_diplayed))
        {
            return '';
        }
        
        //If the label was not defined, we take default label
        $column = $crud->getColumn($column_id);
        if(empty($label))
        {
            $label = $column->label;
        }
        //I18N label
        $label = $this->util->translate($label);
        //XSS protection
        $label = \htmlentities($label);
        
        //Case n°1: We cannot sort this column, we just show the label
        if(!$column->sortable)
        {
            return $this->util->tag('th', $th_options, $label);
        }
        
        //Case n°2: We can sort on this column, but the sorting is not active on her at present
        if($session_values->sort != $column_id)
        {
            $content = $this->listePrivateLink($label, $crud->getUrl().$attribute_page.'sort='.$column_id, array(), $ajax_options);
            return $this->util->tag('th', $th_options, $content);
        }
        
        //Case n°3: We can sort on this column, and the sorting is active on her at present
        $image_src = ($session_values->sense == CrudManager::ASC)? $image_up : $image_down;
        $image_alt = ($session_values->sense == CrudManager::ASC)? 'V' : '^';
        $new_sense = ($session_values->sense == CrudManager::ASC)? CrudManager::DESC : CrudManager::ASC;
        $image = $this->util->tag('img', array('src' => $image_src, 'alt' => $image_alt));
        $link = $this->listePrivateLink($label, $crud->getUrl().$attribute_page.'sense='.$new_sense, array(), $ajax_options);
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
            $value = \htmlentities($value);
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
        $this->javascript_manager->enablejQueryTools();
        
        $form = $this->form_factory->createNamed(new DisplayConfigType($crud), 'crud_display_config');
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
            $ajax_options['update'] = 'js_holder_for_multi_update';
        }
        return $this->javascript_manager->jQueryFormToRemote($crud->getSearchUrl(), $ajax_options, $html_options);
    }
    
    /**
     * Returns search reset button
     * 
     * @param CrudManager $crud
     * @param string $label   Label
     * @param array $ajax_options   Ajax options
     * @param array $html_options   Html options
     * @param string $link_parameter   Suffix url
     * @return string 
     */
    public function searchResetButton(CrudManager $crud, $label, $ajax_options, $html_options, $link_parameter)
    {
        if(!isset($ajax_options['update']))
        {
            $ajax_options['update'] = 'js_holder_for_multi_update';
        }
        if(!isset($html_options['class']))
        {
            $html_options['class'] = 'raz';
        }
        $label = $this->util->translate($label);
        return $this->javascript_manager->jQueryButtonToRemote($label, $crud->getSearchUrl().$link_parameter, $ajax_options, $html_options);
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
        $this->javascript_manager->enablejQueryTools();
        
        $modal_id = str_replace(' ', '', $modal_id);
        //Create Callback (Opening window)
        $js_modal = "$('#$modal_id .contentWrap').html(data); ";
	$js_modal .= "var api_crud_modal = $('#$modal_id').overlay({oneInstance: false, api: true, ";
	$js_modal .= is_null($js_on_close)? '': "onClose: function() { $js_on_close }";
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
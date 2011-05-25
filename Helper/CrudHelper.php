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
     * @param array $options_ajax   Ajax Options
     * @param int $max_pages_before   Number of displayed pages, before curent page
     * @param int $max_pages_after   Number of displayed pages, after curent page
     * @param string $image_first   Url image "<<"
     * @param string $image_last   Url image ">>"
     * @param string $image_previous   Url image "<"
     * @param sting $image_next   Url image ">"
     * @param sring $attribute_page   Text between the url page and the num page (ex. : "?page=")
     * @return string 
     */
    public function paginatorLinks(CrudManager $crud, $options_ajax = array(), $max_pages_before = 3, $max_pages_after = 3, $image_first = '/bundles/ecommitcrud/images/i16/resultset_first.png', $image_last = '/bundles/ecommitcrud/images/i16/resultset_last.png', $image_previous = '/bundles/ecommitcrud/images/i16/resultset_previous.png', $image_next = '/bundles/ecommitcrud/images/i16/resultset_next.png', $attribute_page = '?page=')
    {
        if(!isset($options_ajax['update']))
        {
            $options_ajax['update'] = 'crud_list';
        }
        $paginator = $crud->getPaginator();
        $url = $crud->getUrl();
        
        $navigation = '';
	if ($paginator->haveToPaginate())
	{
            //First page / Previous page
            if ($paginator->getPage() != 1)
            {
                $navigation .= $this->listePrivateLink($this->util->tag('img', array('src' => $image_first, 'alt' => 'first', 'style' => 'vertical-align: top;')), $url.$attribute_page.'1', array(), $options_ajax);
                $navigation .= $this->listePrivateLink($this->util->tag('img', array('src' => $image_previous, 'alt' => 'previous', 'style' => 'vertical-align: top;')), $url.$attribute_page.$paginator->getPreviousPage(), array(), $options_ajax).' ';
            }
            
            //Links
            $links = array();
            
            //Pages before the current page
            for($page = $paginator->getPage() - $max_pages_before; $page < $paginator->getPage(); $page++)
            {
                if($page <= $paginator->getLastPage() && $page >= $paginator->getFirstPage())
                {
                    //The page exists, displays it
                    $links[] = $this->listePrivateLink($page, $url.$attribute_page.$page, array('class' => 'pagination_no_actual'), $options_ajax);
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
                    $links[] = $this->listePrivateLink($page, $url.$attribute_page.$page, array('class' => 'pagination_no_actual'), $options_ajax);
                }
            }
            
            $navigation .= \join('&nbsp;&nbsp;', $links);
            
            //Next page / Last page
            if ($paginator->getPage() != $paginator->getLastPage())
	    {
                $navigation .= '&nbsp;&nbsp;'.$this->listePrivateLink($this->util->tag('img', array('src' => $image_next, 'alt' => 'next', 'style' => 'vertical-align: top;')), $url.$attribute_page.$paginator->getNextPage(), array(), $options_ajax);
                $navigation .= $this->listePrivateLink($this->util->tag('img', array('src' => $image_last, 'alt' => 'last', 'style' => 'vertical-align: top;')), $url.$attribute_page.$paginator->getLastPage(), array(), $options_ajax);
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
     * @param array $options_ajax   Ajax Options
     * @param string $label   Label. If null, default label is displayed
     * @param string $image_up   Url image "^"
     * @param string $image_down   Url image "V"
     * @param string $attribute_page   Text between the url page and attributes (ex. : "?")
     * @return string 
     */
    public function th($column_id, CrudManager $crud, $th_options = array(), $options_ajax = array(), $label = null, $image_up = '/bundles/ecommitcrud/images/i16/sort_incr.png', $image_down = '/bundles/ecommitcrud/images/i16/sort_decrease.png', $attribute_page = '?')
    {
        if(!isset($options_ajax['update']))
        {
            $options_ajax['update'] = 'crud_list';
        }

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
            $content = $this->listePrivateLink($label, $crud->getUrl().$attribute_page.'sort='.$column_id, array(), $options_ajax);
            return $this->util->tag('th', $th_options, $content);
        }
        
        //Case n°3: We can sort on this column, and the sorting is active on her at present
        $image_src = ($session_values->sense == CrudManager::ASC)? $image_up : $image_down;
        $image_alt = ($session_values->sense == CrudManager::ASC)? 'V' : '^';
        $new_sense = ($session_values->sense == CrudManager::ASC)? CrudManager::DESC : CrudManager::ASC;
        $image = $this->util->tag('img', array('src' => $image_src, 'alt' => $image_alt));
        $link = $this->listePrivateLink($label, $crud->getUrl().$attribute_page.'sense='.$new_sense, array(), $options_ajax);
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
    public function td($column_id, CrudManager $crud, $value, $escape = true, $td_options = array())
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
        
        $form = $this->form_factory->create(new DisplayConfigType($crud));
        return $form->createView();
    }
    
    /**
     * Creates a link
     * 
     * @param string $name   Name link
     * @param string $url   Url link
     * @param array $options_link_to   Html options
     * @param array $options_ajax   Ajax options (if null, Ajax is dissabled)
     * @return string 
     */
    protected function listePrivateLink($name, $url, $options_link_to = array(), $options_ajax = null)
    {
	if(is_null($options_ajax))
	{
            //No Ajax, Simple link
            $options_link_to['href'] = $url;
            return $this->util->tag('a', $options_link_to, $name);
	}
	else
	{
            //Ajax Request
            return $this->javascript_manager->jQueryLinkToRemote($name, $url, $options_ajax, $options_link_to);
	}
    }
}
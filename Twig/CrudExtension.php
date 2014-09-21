<?php

/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\Twig;

use Ecommit\CrudBundle\Crud\Crud;
use Ecommit\CrudBundle\Helper\CrudHelper;
use Ecommit\CrudBundle\Paginator\AbstractPaginator;
use Twig_Environment;
use Twig_Extension;
use Twig_SimpleFunction;

class CrudExtension extends Twig_Extension
{
    /**
     * @var CrudHelper
     */
    protected $crudHelper;

    /**
     * @var Twig_Environment
     */
    protected $templating;


    /**
     * Constructor
     *
     * @param CrudHelper $crudHelper
     */
    public function __construct(CrudHelper $crudHelper, Twig_Environment $templating)
    {
        $this->crudHelper = $crudHelper;
        $this->templating = $templating;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'ecommit_crud_crud_extension';
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions()
    {
        return array(
            new Twig_SimpleFunction(
                'paginator_links',
                array($this, 'paginatorLinks'),
                array('is_safe' => array('all'))
            ),
            new Twig_SimpleFunction(
                'crud_paginator_links',
                array($this, 'crudPaginatorLinks'),
                array('is_safe' => array('all'))
            ),
            new Twig_SimpleFunction(
                'crud_th',
                array($this, 'th'),
                array('is_safe' => array('all'))
            ),
            new Twig_SimpleFunction(
                'crud_td',
                array($this, 'td'),
                array('is_safe' => array('all'))
            ),
            new Twig_SimpleFunction(
                'crud_display_settings',
                array($this, 'displaySettings'),
                array('is_safe' => array('all'))
            ),
            new Twig_SimpleFunction(
                'crud_search_form',
                array($this, 'searchForm'),
                array('is_safe' => array('all'))
            ),
            new Twig_SimpleFunction(
                'crud_search_reset',
                array($this, 'searchReset'),
                array('is_safe' => array('all'))
            ),
            new Twig_SimpleFunction(
                'crud_declare_modal',
                array($this, 'declareModal'),
                array('is_safe' => array('all'))
            ),
            new Twig_SimpleFunction(
                'crud_remote_modal',
                array($this, 'remoteModal'),
                array('is_safe' => array('all'))
            ),
            new Twig_SimpleFunction(
                'crud_form_modal',
                array($this, 'formModal'),
                array('is_safe' => array('all'))
            ),
        );
    }

    /**
     * Twig function: "paginator_links"
     *
     * @see CrudHelper:paginatorLinks
     */
    public function paginatorLinks(
        AbstractPaginator $paginator,
        $routeName,
        $routeParams = array(),
        $options = array()
    ) {
        return $this->crudHelper->paginatorLinks($paginator, $routeName, $routeParams, $options);
    }

    /**
     * Twig function: "crud_paginator_links"
     *
     * @see CrudHelper:crudPaginatorLinks
     */
    public function crudPaginatorLinks(Crud $crud, $options = array(), $ajaxOptions = array())
    {
        return $this->crudHelper->crudPaginatorLinks($crud, $options, $ajaxOptions);
    }

    /**
     * Twig function: "crud_th"
     *
     * @see CrudHelper:th
     */
    public function th($columnId, Crud $crud, $options = array(), $thOptions = array(), $ajaxOptions = array())
    {
        return $this->crudHelper->th($columnId, $crud, $options, $thOptions, $ajaxOptions);
    }

    /**
     * Twig function: "crud_td"
     *
     * @see CrudHelper:td
     */
    public function td($columnId, Crud $crud, $value, $escape = true, $tdOptions = array())
    {
        return $this->crudHelper->td($columnId, $crud, $value, $escape, $tdOptions);
    }

    /**
     * Twig function: "crud_display_config"
     *
     * @param Crud $crud
     * @param array $options Options :
     *        * modal:  Include (or not) inside a modal. Default: true
     *        * image_url: Url image (button)
     * @param array $ajaxOptions Ajax options
     * @return string
     */
    public function displaySettings(Crud $crud, $options = array(), $ajaxOptions = array(), $closeDivClass = 'overlay-close')
    {
        $defaultOptions = array(
            'modal' => true,
            'image_url' => '/bundles/ecommitcrud/images/i16/list.png',
        );
        $options = \array_merge($defaultOptions, $options);
        if (!isset($ajaxOptions['update'])) {
            $ajaxOptions['update'] = $crud->getDivIdList();
        }

        $form = $this->crudHelper->getFormDisplaySettings($crud);
        $templateName = $options['modal'] ? 'EcommitCrudBundle:Crud:form_settings_modal.html.twig' : 'EcommitCrudBundle:Crud:form_settings_nomodal.html.twig';

        return $this->templating->render(
            $templateName,
            array(
                'form' => $form,
                'url' => $crud->getUrl(),
                'reset_settings_url' => $crud->getUrl(array('razsettings' => 1)),
                'ajax_options' => $ajaxOptions,
                'image_url' => $options['image_url'],
                'suffix' => $crud->getSessionName(),
                'use_bootstrap' => $this->crudHelper->useBootstrap(),
                'close_div_class' => $closeDivClass,
            )
        );
    }

    /**
     * Twig function: "crud_search_form"
     *
     * @see CrudHelper:searchFormTag
     */
    public function searchForm(Crud $crud, $ajaxOptions = array(), $htmlOptions = array())
    {
        return $this->crudHelper->searchFormTag($crud, $ajaxOptions, $htmlOptions);
    }

    /**
     * Twig function: "crud_search_reset"
     *
     * @see CrudHelper:searchResetButton
     */
    public function searchReset(Crud $crud, $options = array(), $ajaxOptions = array(), $htmlOptions = array())
    {
        return $this->crudHelper->searchResetButton($crud, $options, $ajaxOptions, $htmlOptions);
    }

    /**
     * Twig function: "crud_declare_modal"
     *
     * @see CrudHelper:declareModal
     */
    public function declareModal($modalId, $closeDivClass = 'overlay-close')
    {
        return $this->crudHelper->declareModal($modalId, $closeDivClass);
    }

    /**
     * Twig function: "crud_remote_modal"
     *
     * @see CrudHelper:remoteModal
     */
    public function remoteModal($modalId, $url, $jsOnClose = null, $ajaxOptions = array(), $closeDivClass = 'overlay-close')
    {
        return $this->crudHelper->remoteModal($modalId, $url, $jsOnClose, $ajaxOptions, $closeDivClass);
    }

    /**
     * Twig function: "crud_form_modal"
     *
     * @see CrudHelper:formModal
     */
    public function formModal($modalId, $url, $ajaxOptions = array(), $htmlOptions = array())
    {
        return $this->crudHelper->formModal($modalId, $url, $ajaxOptions, $htmlOptions);
    }
}

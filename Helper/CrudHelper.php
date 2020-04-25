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

use Ecommit\CrudBundle\Crud\Crud;
use Ecommit\CrudBundle\Form\Type\DisplaySettingsType;
use Ecommit\CrudBundle\Paginator\AbstractPaginator;
use Ecommit\JavascriptBundle\Helper\JqueryHelper;
use Ecommit\JavascriptBundle\Overlay\AbstractOverlay;
use Ecommit\UtilBundle\Helper\UtilHelper;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Markup;
use Twig_Environment;

class CrudHelper
{
    /**
     * @var UtilHelper
     */
    protected $util;

    /**
     * @var JqueryHelper
     */
    protected $javascriptManager;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var Twig_Environment
     */
    protected $templating;

    /**
     * @var AbstractOverlay
     */
    protected $overlay;

    /**
     * @var bool
     */
    protected $useBootstrap;

    /**
     * @var array
     */
    protected $parameters;

    /**
     * @var array
     */
    protected $lastValues = array();

    /**
     * Constructor
     *
     * @param UtilHelper $util
     * @param Manager $javascriptManager
     * @param FormFactoryInterface $formFactory
     * @param bool $useBootstrap
     */
    public function __construct(
        UtilHelper $util,
        JqueryHelper $javascriptManager,
        FormFactoryInterface $formFactory,
        RouterInterface $router,
        TranslatorInterface $translator,
        Twig_Environment $templating,
        AbstractOverlay $overlay,
        $parameters
    ) {
        $this->util = $util;
        $this->javascriptManager = $javascriptManager;
        $this->formFactory = $formFactory;
        $this->router = $router;
        $this->translator = $translator;
        $this->templating = $templating;
        $this->overlay = $overlay;
        $this->parameters = $parameters;
        $this->useBootstrap = $parameters['use_bootstrap'];
    }

    /**
     * @return bool
     */
    public function useBootstrap()
    {
        return $this->useBootstrap;
    }

    /**
     * @return AbstractOverlay
     */
    public function getOverlayService()
    {
        return $this->overlay;
    }

    /**
     * Returns links paginator
     *
     * @param AbstractPaginator $paginator
     * @param string $routeName Route name
     * @param array $routeParams Route parameters
     * @param array $options Options:
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
     *        * use_bootstrap: Use or not bootstap
     *        * bootstrap_size: Bootstrap nav size (lg sm or null)
     *        * template: Template used. If null, default template is used
     * @return string
     */
    public function paginatorLinks(AbstractPaginator $paginator, $routeName, $routeParams, $options)
    {
        if (isset($this->parameters['template_configuration']['paginator_links'])) {
            $options = array_merge($this->parameters['template_configuration']['paginator_links'], $options);
        }
        $resolver = new OptionsResolver();
        $resolver->setDefaults(
            array(
                'ajax_options' => null,
                'attribute_page' => 'page',
                'type' => 'sliding',
                'max_pages_before' => 3,
                'max_pages_after' => 3,
                'buttons' => 'text',
                'image_first' => '/bundles/ecommitcrud/images/i16/resultset_first.png',
                'image_previous' => '/bundles/ecommitcrud/images/i16/resultset_previous.png',
                'image_next' => '/bundles/ecommitcrud/images/i16/resultset_next.png',
                'image_last' => '/bundles/ecommitcrud/images/i16/resultset_last.png',
                'text_first' => '&laquo;',
                'text_previous' => '&lsaquo;',
                'text_next' => '&rsaquo;',
                'text_last' => '&raquo;',
                'use_bootstrap' => $this->useBootstrap,
                'bootstrap_size' => null,
                'template' => null,
            )
        );
        $resolver->setAllowedTypes('ajax_options', array('null', 'array'));
        $resolver->setAllowedTypes('max_pages_before', 'int');
        $resolver->setAllowedTypes('max_pages_after', 'int');
        $resolver->setAllowedValues('type', array('sliding', 'elastic'));
        $resolver->setAllowedValues('buttons', array('text', 'image'));
        $resolver->setAllowedTypes('use_bootstrap', 'bool');
        $resolver->setAllowedValues('bootstrap_size', array('lg', 'sm', null));
        $options = $resolver->resolve($options);

        if ($options['template']) {
            return $this->templating->render(
                $options['template'],
                array(
                    'paginator' => $paginator,
                    'routeName' => $routeName,
                    'routeParams' => $routeParams,
                    'options' => $options,
                )
            );
        }

        $navigation = '';
        if ($paginator->haveToPaginate()) {
            $navigationClass = $options['use_bootstrap']? 'pagination': 'pagination_nobootstrap';
            if ($options['use_bootstrap'] && $options['bootstrap_size']) {
                $navigationClass .= ' pagination-'.$options['bootstrap_size'];
            }
            $navigation .= \sprintf('<nav><ul class="%s">', $navigationClass);

            //First page / Previous page
            if ($paginator->getPage() != 1) {
                $navigation .= $this->elementPaginatorLinks(1, $options, 'first', $routeName, $routeParams);
                $navigation .= $this->elementPaginatorLinks(
                    $paginator->getPreviousPage(),
                    $options,
                    'previous',
                    $routeName,
                    $routeParams
                );
            }

            //Pages before the current page
            $limit = ($options['type'] == 'sliding') ? $paginator->getPage() - $options['max_pages_before'] : 1;
            $currentPage = $paginator->getPage();
            for ($page = $limit; $page < $currentPage; $page++) {
                if ($page <= $paginator->getLastPage() && $page >= $paginator->getFirstPage()) {
                    //The page exists, displays it
                    $navigation .= $this->elementPaginatorLinks($page, $options, 'page', $routeName, $routeParams);
                }
            }

            //Current page
            $navigation .= $this->elementPaginatorLinks(
                $paginator->getPage(),
                $options,
                'page',
                $routeName,
                $routeParams,
                true
            );

            //Pages after the current page
            $limit = ($options['type'] == 'sliding') ? $paginator->getPage(
                ) + $options['max_pages_after'] : $paginator->getLastPage();
            for ($page = $paginator->getPage() + 1; $page <= $limit; $page++) {
                if ($page <= $paginator->getLastPage() && $page >= $paginator->getFirstPage()) {
                    //The page exists, displays it
                    $navigation .= $this->elementPaginatorLinks($page, $options, 'page', $routeName, $routeParams);
                }
            }

            //Next page / Last page
            if ($paginator->getPage() != $paginator->getLastPage()) {
                $navigation .= $this->elementPaginatorLinks(
                    $paginator->getNextPage(),
                    $options,
                    'next',
                    $routeName,
                    $routeParams
                );
                $navigation .= $this->elementPaginatorLinks(
                    $paginator->getLastPage(),
                    $options,
                    'last',
                    $routeName,
                    $routeParams
                );
            }

            $navigation .= '</ul></nav>';
        }

        return $navigation;
    }

    /**
     * Paginator links: Display one element
     *
     * @param int $page Page number
     * @param array $options Options
     * @param string $elementName first, previous, page, next or last
     * @param string $routeName
     * @param array $routeParams
     * @param bool $current If element is the current page
     */
    protected function elementPaginatorLinks(
        $page,
        $options,
        $elementName,
        $routeName,
        $routeParams,
        $current = false
    ) {
        $url = $this->router->generate(
            $routeName,
            \array_merge($routeParams, array($options['attribute_page'] => $page))
        );
        if ($elementName == 'page') {
            if ($current) {
                $class = ($options['use_bootstrap'])? 'active': 'pagination_current';
                if ($options['use_bootstrap']) {
                    $content = $this->listePrivateLink($page, '', array('onclick' => 'return false;')); //bootstrap expects link
                } else {
                    $content = $page;
                }
            } else {
                $class = ($options['use_bootstrap'])? '': 'pagination_no_current';
                $content = $this->listePrivateLink($page, $url, array(), $options['ajax_options']);
            }
        } else {
            $class = $options['buttons'] . ' ' . $elementName;
            $buttonName = $options['buttons'] . '_' . $elementName;
            $button = $options[$buttonName];
            if ($options['buttons'] == 'text' || $options['use_bootstrap']) {
                $content = $this->listePrivateLink($button, $url, array(), $options['ajax_options']);
            } else {
                $image = $this->util->tag('img', array('src' => $button, 'alt' => $elementName));
                $content = $this->listePrivateLink($image, $url, array(), $options['ajax_options']);
            }
        }

        return \sprintf('<li class="%s">%s</li>', $class, $content);
    }

    /**
     * Returns CRUD links paginator
     *
     * @param Crud $crud
     * @param array $options Options. See CrudHelper::paginatorLinks (ajax_options is ignored)
     * @param array $ajaxOptions Ajax Options
     * @return string
     */
    public function crudPaginatorLinks(Crud $crud, $options, $ajaxOptions)
    {
        $options = array_merge($crud->getTemplateConfiguration('crud_paginator_links'), $options);
        if (!isset($ajaxOptions['update'])) {
            $ajaxOptions['update'] = $crud->getDivIdList();
        }
        $options['ajax_options'] = $ajaxOptions;

        return $this->paginatorLinks($crud->getPaginator(), $crud->getRouteName(), $crud->getRouteParams(), $options);
    }

    /**
     * Returns one colunm, inside "header" CRUD
     *
     * @param string $column_id Column id
     * @param Crud $crud
     * @param array $options Options :
     *        * label: Label. If null, default label is displayed
     *        * image_up: Url image "^"
     *        * image_down: Url image "V"
     *        * template: Template used. If null, default template is used
     * @param array $thOptions Html options
     * @param array $ajaxOptions Ajax Options
     * @return string
     */
    public function th($column_id, Crud $crud, $options, $thOptions, $ajaxOptions)
    {
        $options = array_merge($crud->getTemplateConfiguration('crud_th'), $options);
        $resolver = new OptionsResolver();
        $resolver->setDefaults(
            array(
                'label' => null,
                'image_up' => $this->parameters['images']['th_image_up'],
                'image_down' => $this->parameters['images']['th_image_down'],
                'template' => null,
            )
        );
        $options = $resolver->resolve($options);

        if ($options['template']) {
            return $this->templating->render(
                $options['template'],
                array(
                    'column_id' => $column_id,
                    'crud' => $crud,
                    'th_options' => $thOptions,
                    'ajax_options' => $ajaxOptions,
                )
            );
        }

        if (!isset($ajaxOptions['update'])) {
            $ajaxOptions['update'] = $crud->getDivIdList();
        }
        $image_up = $options['image_up'];
        $image_down = $options['image_down'];

        //If the column is not to be shown, returns empty
        $session_values = $crud->getSessionValues();
        if (!\in_array($column_id, $session_values->displayedColumns)) {
            return '';
        }

        //If the label was not defined, we take default label
        $column = $crud->getColumn($column_id);
        $label = $options['label'];
        if (\is_null($label)) {
            $label = $column->label;
        }
        //I18N label
        $label = $this->translator->trans($label);
        //XSS protection
        $label = \htmlentities($label, ENT_QUOTES, 'UTF-8');

        //Case n°1: We cannot sort this column, we just show the label
        if (!$column->sortable) {
            return $this->util->tag('th', $thOptions, $label);
        }

        //Case n°2: We can sort on this column, but the sorting is not active on her at present
        if ($session_values->sort != $column_id) {
            $content = $this->listePrivateLink(
                $label,
                $crud->getUrl(array('sort' => $column_id)),
                array(),
                $ajaxOptions
            );

            return $this->util->tag('th', $thOptions, $content);
        }

        //Case n°3: We can sort on this column, and the sorting is active on her at present
        $image_src = ($session_values->sense == Crud::ASC) ? $image_up : $image_down;
        $image_alt = ($session_values->sense == Crud::ASC) ? 'V' : '^';
        $new_sense = ($session_values->sense == Crud::ASC) ? Crud::DESC : Crud::ASC;
        $image = $this->util->tag('img', array('src' => $image_src, 'alt' => $image_alt));
        $link = $this->listePrivateLink($label, $crud->getUrl(array('sense' => $new_sense)), array(), $ajaxOptions);

        return $this->util->tag('th', $thOptions, $link . $image);
    }

    /**
     * Returns one colunm, inside "body" CRUD
     *
     * @param string $column_id Column id
     * @param Crud $crud
     * @param string $value Value
     * @param array $options Options :
     *        * escape: Escape (or not) the value
     *        * template: Template used. If null, default template is used
     * @param array $tdOptions Html options
     * @return string
     */
    public function td($column_id, Crud $crud, $value, $options, $tdOptions)
    {
        $options = array_merge($crud->getTemplateConfiguration('crud_td'), $options);
        $resolver = new OptionsResolver();
        $resolver->setDefaults(
            array(
                'escape' => true,
                'template' => null,
                'repeated_values_string' => null,
                'repeated_values_add_title' => true,
            )
        );
        $options = $resolver->resolve($options);

        if ($options['template']) {
            return $this->templating->render(
                $options['template'],
                array(
                    'column_id' => $column_id,
                    'crud' => $crud,
                    'value' => $value,
                    'options' => $options,
                    'td_options' => $tdOptions,
                )
            );
        }

        //If the column is not to be shown, returns empty
        $session_values = $crud->getSessionValues();
        if (!\in_array($column_id, $session_values->displayedColumns)) {
            return '';
        }

        //Repeated values
        if (null !== $options['repeated_values_string']) {
            if ($value instanceof Markup) {
                $value = $value->__toString();
            }
            if (null === $value) {
                $value = '';
            }

            if (isset($this->lastValues[$column_id]) && $this->lastValues[$column_id] === $value) {
                if ('' !== $value) {
                    if ($options['repeated_values_add_title']) {
                        $tdOptions['title'] = $value;
                    }
                    $value = $options['repeated_values_string'];
                }
            } else {
                $this->lastValues[$column_id] = $value;
            }
        }

        //XSS protection
        if ($options['escape']) {
            $value = \htmlentities($value, ENT_QUOTES, 'UTF-8');
        }

        return $this->util->tag('td', $tdOptions, $value);
    }

    /**
     * Returns "Display Settings" form
     *
     * @param Crud $crud
     * @return FormView
     */
    public function getFormDisplaySettings(Crud $crud)
    {
        $form_name = sprintf('crud_display_settings_%s', $crud->getSessionName());
        $resultsPerPageChoices = array();
        foreach ($crud->getAvailableResultsPerPage() as $number) {
            $resultsPerPageChoices[$number] = $number;
        }
        $columnsChoices = array();
        foreach ($crud->getColumns() as $column) {
            $columnsChoices[$column->id] = $column->label;
        }
        $data = array(
            'resultsPerPage' => $crud->getSessionValues()->resultsPerPage,
            'displayedColumns' => $crud->getSessionValues()->displayedColumns,
        );

        $form = $this->formFactory->createNamed(
            $form_name,
            DisplaySettingsType::class,
            $data,
            array(
                'resultsPerPageChoices' => $resultsPerPageChoices,
                'columnsChoices' => $columnsChoices,
                'action' => $crud->getUrl(),
            )
        );

        return $form->createView();
    }

    /**
     * Returns search form tag
     *
     * @param Crud $crud
     * @param array $ajaxOptions Ajax Options
     * @param type $htmlOptions Html options
     * @return string
     */
    public function searchFormTag(Crud $crud, $ajaxOptions, $htmlOptions)
    {
        if (!isset($ajaxOptions['update'])) {
            $ajaxOptions['update'] = 'js_holder_for_multi_update_' . $crud->getSessionName();
        }

        if (!isset($htmlOptions['novalidate'])) {
            $htmlOptions['novalidate'] = 'novalidate';
        }

        return $this->javascriptManager->jQueryFormToRemote($crud->getSearcherForm(), $ajaxOptions, $htmlOptions);
    }

    /**
     * Returns search reset button
     *
     * @param Crud $crud
     * @param array $options Options :
     *        * label: Label. Défault: Reset
     *        * template: Template used. If null, default template is used
     * @param array $ajaxOptions Ajax options
     * @param array $htmlOptions Html options
     * @return string
     */
    public function searchResetButton(Crud $crud, $options, $ajaxOptions, $htmlOptions)
    {
        $options = array_merge($crud->getTemplateConfiguration('crud_search_reset'), $options);
        $resolver = new OptionsResolver();
        $resolver->setDefaults(
            array(
                'label' => 'Reset',
                'template' => null,
            )
        );
        $options = $resolver->resolve($options);

        if ($options['template']) {
            return $this->templating->render(
                $options['template'],
                array(
                    'crud' => $crud,
                    'options' => $options,
                    'ajax_options' => $ajaxOptions,
                    'html_options' => $htmlOptions,
                )
            );
        }

        if (!isset($ajaxOptions['update'])) {
            $ajaxOptions['update'] = 'js_holder_for_multi_update_' . $crud->getSessionName();
        }
        if (!isset($htmlOptions['class'])) {
            $htmlOptions['class'] = ($this->useBootstrap) ? 'raz-bootstrap btn btn-default btn-sm' : 'raz';
        }
        $label = $this->translator->trans($options['label']);
        if ($this->useBootstrap) {
            $label = '<span class="glyphicon glyphicon-fire"></span> ' . $label;
        }

        return $this->javascriptManager->jQueryButtonToRemote(
            $label,
            $crud->getSearchUrl(array('raz' => 1)),
            $ajaxOptions,
            $htmlOptions
        );
    }

    /**
     * Returns declaration of modal
     *
     * @param string $modalId Modal id
     * @param array $options
     * @return string
     */
    public function declareModal($modalId, $options = array())
    {
        return $this->overlay->declareHtmlModal($modalId, $options);
    }

    /**
     * Returns JS code to open modal window
     *
     * @param string $modalId Modal id
     * @param string $url Url
     * @param array $options Array options
     * @param array $ajaxOptions Ajax options
     * @return string
     */
    public function remoteModal($modalId, $url, $options, $ajaxOptions)
    {
        if (isset($this->parameters['template_configuration']['crud_remote_modal'])) {
            $options = array_merge($this->parameters['template_configuration']['crud_remote_modal'], $options);
        }
        $modalId = str_replace(' ', '', $modalId);

        $resolver = new OptionsResolver();
        $resolver->setDefaults(
            array(
                'js_on_close' => null,
                'close_div_class' => 'overlay-close',
            )
        );
        $options = $resolver->resolve($options);

        //Create Callback (Opening window)
        $jsModal = "$('#$modalId .contentWrap').html(data); ";
        $jsModal .= $this->overlay->declareJavascriptModal($modalId, array('js_on_close' => $options['js_on_close'], 'close_div_class' => $options['close_div_class']));
        $jsModal .= $this->overlay->openModal($modalId);

        //Add callback
        if (isset($ajaxOptions['success'])) {
            $ajaxOptions['success'] = $jsModal . ' ' . $ajaxOptions['success'];
        } else {
            $ajaxOptions['success'] = $jsModal;
        }

        //Method
        if (!isset($ajaxOptions['method'])) {
            $ajaxOptions['method'] = 'GET';
        }

        return $this->javascriptManager->jQueryRemoteFunction($url, $ajaxOptions);
    }

    /**
     * Returns modal form tag
     *
     * @param string $modalId Modal id
     * @param FormView|string $form The form or the url.
     * @param array $ajaxOptions Ajax options
     * @param array $htmlOptions Html options
     * @return string
     */
    public function formModal($modalId, $form, $ajaxOptions, $htmlOptions)
    {
        $modalId = str_replace(' ', '', $modalId);
        if (!isset($ajaxOptions['update'])) {
            $ajaxOptions['update'] = $modalId . ' .contentWrap';
        }

        return $this->javascriptManager->jQueryFormToRemote($form, $ajaxOptions, $htmlOptions);
    }

    /**
     * Creates a link
     *
     * @param string $name Name link
     * @param string $url Url link
     * @param array $linkToOptions Html options
     * @param array $ajaxOptions Ajax options (if null, Ajax is disabled)
     * @return string
     */
    protected function listePrivateLink($name, $url, $linkToOptions = array(), $ajaxOptions = null)
    {
        if (is_null($ajaxOptions)) {
            //No Ajax, Simple link
            $linkToOptions['href'] = $url;

            return $this->util->tag('a', $linkToOptions, $name);
        } else {
            //Ajax Request
            return $this->javascriptManager->jQueryLinkToRemote($name, $url, $ajaxOptions, $linkToOptions);
        }
    }
}

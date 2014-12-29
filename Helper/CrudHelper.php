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
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

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
     * @var FormFactory
     */
    protected $formFactory;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var AbstractOverlay
     */
    protected $overlay;

    /**
     * @var bool
     */
    protected $useBoostrap;

    /**
     * Constructor
     *
     * @param UtilHelper $util
     * @param Manager $javascriptManager
     * @param FormFactory $formFactory
     * @param bool $useBoostrap
     */
    public function __construct(
        UtilHelper $util,
        JqueryHelper $javascriptManager,
        FormFactory $formFactory,
        Router $router,
        TranslatorInterface $translator,
        AbstractOverlay $overlay,
        $useBoostrap
    ) {
        $this->util = $util;
        $this->javascriptManager = $javascriptManager;
        $this->formFactory = $formFactory;
        $this->router = $router;
        $this->translator = $translator;
        $this->overlay = $overlay;
        $this->useBoostrap = $useBoostrap;
    }

    /**
     * @return bool
     */
    public function useBootstrap()
    {
        return $this->useBoostrap;
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
     * @return string
     */
    public function paginatorLinks(AbstractPaginator $paginator, $routeName, $routeParams, $options)
    {
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
                'text_first' => '<<',
                'text_previous' => '<',
                'text_next' => '>',
                'text_last' => '>>',
            )
        );
        $resolver->setAllowedTypes('ajax_options', array('null', 'array'));
        $resolver->setAllowedTypes('max_pages_before', 'int');
        $resolver->setAllowedTypes('max_pages_after', 'int');
        $resolver->setAllowedValues('type', array('sliding', 'elastic'));
        $resolver->setAllowedValues('buttons', array('text', 'image'));
        $options = $resolver->resolve($options);

        $navigation = '';
        if ($paginator->haveToPaginate()) {
            $navigation .= '<div class="pagination">';

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

            $navigation .= '</div>';
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
                $class = 'pagination_current';
                $content = $page;
            } else {
                $class = 'pagination_no_current';
                $content = $this->listePrivateLink($page, $url, array(), $options['ajax_options']);
            }
        } else {
            $class = $options['buttons'] . ' ' . $elementName;
            $buttonName = $options['buttons'] . '_' . $elementName;
            $button = $options[$buttonName];
            if ($options['buttons'] == 'text') {
                $content = $this->listePrivateLink($button, $url, array(), $options['ajax_options']);
            } else {
                $image = $this->util->tag('img', array('src' => $button, 'alt' => $elementName));
                $content = $this->listePrivateLink($image, $url, array(), $options['ajax_options']);
            }
        }

        return \sprintf('<span class="%s">%s</span>', $class, $content);
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
     * @param array $thOptions Html options
     * @param array $ajaxOptions Ajax Options
     * @return string
     */
    public function th($column_id, Crud $crud, $options, $thOptions, $ajaxOptions)
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(
            array(
                'label' => null,
                'image_up' => '/bundles/ecommitcrud/images/i16/sort_incr.png',
                'image_down' => '/bundles/ecommitcrud/images/i16/sort_decrease.png',
            )
        );
        $options = $resolver->resolve($options);

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
     * @param bool $escape Escape (or not) the value
     * @param array $tdOptions Html options
     * @return string
     */
    public function td($column_id, Crud $crud, $value, $escape, $tdOptions)
    {
        //If the column is not to be shown, returns empty
        $session_values = $crud->getSessionValues();
        if (!\in_array($column_id, $session_values->displayedColumns)) {
            return '';
        }

        //XSS protection
        if ($escape) {
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
            new DisplaySettingsType(),
            $data,
            array(
                'resultsPerPageChoices' => $resultsPerPageChoices,
                'columnsChoices' => $columnsChoices,
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

        return $this->javascriptManager->jQueryFormToRemote($crud->getSearchUrl(), $ajaxOptions, $htmlOptions);
    }

    /**
     * Returns search reset button
     *
     * @param Crud $crud
     * @param array $options Options :
     *        * label: Label. Défault: Reset
     * @param array $ajaxOptions Ajax options
     * @param array $htmlOptions Html options
     * @return string
     */
    public function searchResetButton(Crud $crud, $options, $ajaxOptions, $htmlOptions)
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(
            array(
                'label' => 'Reset',
            )
        );
        $options = $resolver->resolve($options);

        if (!isset($ajaxOptions['update'])) {
            $ajaxOptions['update'] = 'js_holder_for_multi_update_' . $crud->getSessionName();
        }
        if (!isset($htmlOptions['class'])) {
            $htmlOptions['class'] = ($this->useBoostrap) ? 'raz-bootstrap btn btn-default btn-sm' : 'raz';
        }
        $label = $this->translator->trans($options['label']);
        if ($this->useBoostrap) {
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
     * @param string $closeDivClass Class Div
     * @param bool $useBootstrap  Use bootstrap or not. If null, default option is used
     * @return string
     */
    public function declareModal($modalId, $closeDivClass = 'overlay-close', $useBootstrap = null)
    {
        return $this->overlay->declareHtmlModal($modalId, null, $closeDivClass, $useBootstrap);
    }

    /**
     * Returns JS code to open modal window
     *
     * @param string $modalId Modal id
     * @param string $url Url
     * @param string $jsOnClose JS code excuted, during the closure of the modal
     * @param array $ajaxOptions Ajax options
     * @return string
     */
    public function remoteModal($modalId, $url, $jsOnClose, $ajaxOptions, $closeDivClass = 'overlay-close')
    {
        $modalId = str_replace(' ', '', $modalId);
        //Create Callback (Opening window)
        $jsModal = "$('#$modalId .contentWrap').html(data); ";
        $jsModal .= $this->overlay->declareJavascriptModal($modalId, null, $jsOnClose, $closeDivClass);
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
     * @param string $url Url
     * @param array $ajaxOptions Ajax options
     * @param array $htmlOptions Html options
     * @return string
     */
    public function formModal($modalId, $url, $ajaxOptions, $htmlOptions)
    {
        $modalId = str_replace(' ', '', $modalId);
        if (!isset($ajaxOptions['update'])) {
            $ajaxOptions['update'] = $modalId . ' .contentWrap';
        }

        return $this->javascriptManager->jQueryFormToRemote($url, $ajaxOptions, $htmlOptions);
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

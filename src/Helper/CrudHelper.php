<?php

declare(strict_types=1);

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
use Ecommit\JavascriptBundle\Helper\JqueryHelper;
use Ecommit\JavascriptBundle\Overlay\AbstractOverlay;
use Ecommit\UtilBundle\Helper\UtilHelper;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
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
     * Constructor.
     *
     * @param Manager $javascriptManager
     * @param bool    $useBootstrap
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
     * Returns "Display Settings" form.
     *
     * @return FormView
     */
    public function getFormDisplaySettings(Crud $crud)
    {
        $form_name = sprintf('crud_display_settings_%s', $crud->getSessionName());
        $resultsPerPageChoices = [];
        foreach ($crud->getAvailableResultsPerPage() as $number) {
            $resultsPerPageChoices[$number] = $number;
        }
        $columnsChoices = [];
        foreach ($crud->getColumns() as $column) {
            $columnsChoices[$column->id] = $column->label;
        }
        $data = [
            'resultsPerPage' => $crud->getSessionValues()->resultsPerPage,
            'displayedColumns' => $crud->getSessionValues()->displayedColumns,
        ];

        $form = $this->formFactory->createNamed(
            $form_name,
            DisplaySettingsType::class,
            $data,
            [
                'resultsPerPageChoices' => $resultsPerPageChoices,
                'columnsChoices' => $columnsChoices,
                'action' => $crud->getUrl(),
            ]
        );

        return $form->createView();
    }

    /**
     * Returns search form tag.
     *
     * @param array $ajaxOptions Ajax Options
     * @param type  $htmlOptions Html options
     *
     * @return string
     */
    public function searchFormTag(Crud $crud, $ajaxOptions, $htmlOptions)
    {
        if (!isset($ajaxOptions['update'])) {
            $ajaxOptions['update'] = 'js_holder_for_multi_update_'.$crud->getSessionName();
        }

        if (!isset($htmlOptions['novalidate'])) {
            $htmlOptions['novalidate'] = 'novalidate';
        }

        return $this->javascriptManager->jQueryFormToRemote($crud->getSearcherForm(), $ajaxOptions, $htmlOptions);
    }

    /**
     * Returns search reset button.
     *
     * @param array $options     Options :
     *                           * label: Label. Défault: Reset
     *                           * template: Template used. If null, default template is used
     * @param array $ajaxOptions Ajax options
     * @param array $htmlOptions Html options
     *
     * @return string
     */
    public function searchResetButton(Crud $crud, $options, $ajaxOptions, $htmlOptions)
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(
            [
                'label' => 'Reset',
                'template' => null,
            ]
        );
        $options = $resolver->resolve($options);

        if ($options['template']) {
            return $this->templating->render(
                $options['template'],
                [
                    'crud' => $crud,
                    'options' => $options,
                    'ajax_options' => $ajaxOptions,
                    'html_options' => $htmlOptions,
                ]
            );
        }

        if (!isset($ajaxOptions['update'])) {
            $ajaxOptions['update'] = 'js_holder_for_multi_update_'.$crud->getSessionName();
        }
        if (!isset($htmlOptions['class'])) {
            $htmlOptions['class'] = ($this->useBootstrap) ? 'raz-bootstrap btn btn-default btn-sm' : 'raz';
        }
        $label = $this->translator->trans($options['label']);
        if ($this->useBootstrap) {
            $label = '<span class="glyphicon glyphicon-fire"></span> '.$label;
        }

        return $this->javascriptManager->jQueryButtonToRemote(
            $label,
            $crud->getSearchUrl(['raz' => 1]),
            $ajaxOptions,
            $htmlOptions
        );
    }

    /**
     * Returns declaration of modal.
     *
     * @param string $modalId Modal id
     * @param array  $options
     *
     * @return string
     */
    public function declareModal($modalId, $options = [])
    {
        return $this->overlay->declareHtmlModal($modalId, $options);
    }

    /**
     * Returns JS code to open modal window.
     *
     * @param string $modalId     Modal id
     * @param string $url         Url
     * @param array  $options     Array options
     * @param array  $ajaxOptions Ajax options
     *
     * @return string
     */
    public function remoteModal($modalId, $url, $options, $ajaxOptions)
    {
        $modalId = str_replace(' ', '', $modalId);

        $resolver = new OptionsResolver();
        $resolver->setDefaults(
            [
                'js_on_close' => null,
                'close_div_class' => 'overlay-close',
            ]
        );
        $options = $resolver->resolve($options);

        //Create Callback (Opening window)
        $jsModal = "$('#$modalId .contentWrap').html(data); ";
        $jsModal .= $this->overlay->declareJavascriptModal($modalId, ['js_on_close' => $options['js_on_close'], 'close_div_class' => $options['close_div_class']]);
        $jsModal .= $this->overlay->openModal($modalId);

        //Add callback
        if (isset($ajaxOptions['success'])) {
            $ajaxOptions['success'] = $jsModal.' '.$ajaxOptions['success'];
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
     * Returns modal form tag.
     *
     * @param string          $modalId     Modal id
     * @param FormView|string $form        the form or the url
     * @param array           $ajaxOptions Ajax options
     * @param array           $htmlOptions Html options
     *
     * @return string
     */
    public function formModal($modalId, $form, $ajaxOptions, $htmlOptions)
    {
        $modalId = str_replace(' ', '', $modalId);
        if (!isset($ajaxOptions['update'])) {
            $ajaxOptions['update'] = $modalId.' .contentWrap';
        }

        return $this->javascriptManager->jQueryFormToRemote($form, $ajaxOptions, $htmlOptions);
    }

    /**
     * Creates a link.
     *
     * @param string $name          Name link
     * @param string $url           Url link
     * @param array  $linkToOptions Html options
     * @param array  $ajaxOptions   Ajax options (if null, Ajax is disabled)
     *
     * @return string
     */
    protected function listePrivateLink($name, $url, $linkToOptions = [], $ajaxOptions = null)
    {
        if (null === $ajaxOptions) {
            //No Ajax, Simple link
            $linkToOptions['href'] = $url;

            return $this->util->tag('a', $linkToOptions, $name);
        }
        //Ajax Request
        return $this->javascriptManager->jQueryLinkToRemote($name, $url, $ajaxOptions, $linkToOptions);
    }
}

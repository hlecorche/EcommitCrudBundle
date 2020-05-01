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

namespace Ecommit\CrudBundle\Twig;

use Ecommit\CrudBundle\Crud\Crud;
use Ecommit\CrudBundle\Helper\CrudHelper;
use Ecommit\CrudBundle\Paginator\AbstractPaginator;
use Symfony\Component\Form\FormRendererInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CrudExtension extends AbstractExtension
{
    /**
     * @var CrudHelper
     */
    protected $crudHelper;

    /**
     * @var Environment
     */
    protected $templating;

    /**
     * @var FormRendererInterface
     */
    protected $formRenderer;

    protected $theme;

    /**
     * Constructor.
     */
    public function __construct(CrudHelper $crudHelper, Environment $templating, FormRendererInterface $formRenderer, string $theme)
    {
        $this->crudHelper = $crudHelper;
        $this->templating = $templating;
        $this->formRenderer = $formRenderer;
        $this->theme = $theme;
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
        return [
            new TwigFunction(
                'paginator_links',
                [$this, 'paginatorLinks'],
                ['is_safe' => ['all']]
            ),
            new TwigFunction(
                'crud_paginator_links',
                [$this, 'crudPaginatorLinks'],
                ['is_safe' => ['all']]
            ),
            new TwigFunction(
                'crud_th',
                [$this, 'th'],
                ['is_safe' => ['all']]
            ),
            new TwigFunction(
                'crud_td',
                [$this, 'td'],
                ['is_safe' => ['all']]
            ),
            new TwigFunction(
                'crud_display_settings',
                [$this, 'displaySettings'],
                ['is_safe' => ['all']]
            ),
            new TwigFunction(
                'crud_search_form',
                [$this, 'searchForm'],
                ['is_safe' => ['all']]
            ),
            new TwigFunction(
                'crud_search_reset',
                [$this, 'searchReset'],
                ['is_safe' => ['all']]
            ),
            new TwigFunction(
                'crud_declare_modal',
                [$this, 'declareModal'],
                ['is_safe' => ['all']]
            ),
            new TwigFunction(
                'crud_remote_modal',
                [$this, 'remoteModal'],
                ['is_safe' => ['all']]
            ),
            new TwigFunction(
                'crud_form_modal',
                [$this, 'formModal'],
                ['is_safe' => ['all']]
            ),
            new TwigFunction(
                'form_start_ajax',
                [$this, 'formStartAjax'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'ajax_attributes',
                [$this, 'ajaxAttributes'],
                [
                    'needs_environment' => true,
                    'is_safe' => ['html'],
                ]
            ),
        ];
    }

    /**
     * Twig function: "paginator_links".
     *
     * @see CrudHelper:paginatorLinks
     */
    public function paginatorLinks(
        AbstractPaginator $paginator,
        $routeName,
        $routeParams = [],
        $options = []
    ) {
        return $this->crudHelper->paginatorLinks($paginator, $routeName, $routeParams, $options);
    }

    /**
     * Twig function: "crud_paginator_links".
     *
     * @see CrudHelper:crudPaginatorLinks
     */
    public function crudPaginatorLinks(Crud $crud, $options = [], $ajaxOptions = [])
    {
        return $this->crudHelper->crudPaginatorLinks($crud, $options, $ajaxOptions);
    }

    /**
     * Twig function: "crud_th".
     *
     * @see CrudHelper:th
     */
    public function th($columnId, Crud $crud, $options = [], $thOptions = [], $ajaxOptions = [])
    {
        return $this->crudHelper->th($columnId, $crud, $options, $thOptions, $ajaxOptions);
    }

    /**
     * Twig function: "crud_td".
     *
     * @see CrudHelper:td
     */
    public function td($columnId, Crud $crud, $value, $options = [], $tdOptions = [])
    {
        return $this->crudHelper->td($columnId, $crud, $value, $options, $tdOptions);
    }

    /**
     * Twig function: "crud_display_config".
     *
     * @param array $options     Options :
     *                           * modal:  Include (or not) inside a modal. Default: true
     *                           * image_url: Url image (button)
     *                           * use_bootstrap: Use Bootstrap or not
     *                           * modal_close_div_class: Close Div CSS Class
     *                           * template: Template used. If null, default template is used
     * @param array $ajaxOptions Ajax options
     *
     * @return string
     */
    public function displaySettings(Crud $crud, $options = [], $ajaxOptions = [])
    {
        $options = array_merge($crud->getTemplateConfiguration('crud_display_settings'), $options);
        $resolver = new OptionsResolver();
        $resolver->setDefaults(
            [
                'modal' => true,
                'image_url' => '/bundles/ecommitcrud/images/i16/list.png',
                'use_bootstrap' => $this->crudHelper->useBootstrap(),
                'modal_close_div_class' => 'overlay-close',
                'template' => null,
            ]
        );
        $resolver->setAllowedTypes('modal', 'bool');
        $resolver->setAllowedTypes('use_bootstrap', 'bool');
        $options = $resolver->resolve($options);

        if (!isset($ajaxOptions['update'])) {
            $ajaxOptions['update'] = '#'.$crud->getDivIdList();
        }

        $form = $this->crudHelper->getFormDisplaySettings($crud);

        if (!empty($options['template'])) {
            $templateName = $options['template'];
        } elseif ($options['modal']) {
            if ($options['use_bootstrap']) {
                $templateName = '@EcommitCrud/Crud/form_settings_modal_bootstrap.html.twig';
            } else {
                $templateName = '@EcommitCrud/Crud/form_settings_modal.html.twig';
            }
        } else {
            if ($options['use_bootstrap']) {
                $templateName = '@EcommitCrud/Crud/form_settings_nomodal_bootstrap.html.twig';
            } else {
                $templateName = '@EcommitCrud/Crud/form_settings_nomodal.html.twig';
            }
        }

        return $this->templating->render(
            $templateName,
            [
                'form' => $form,
                'url' => $crud->getUrl(),
                'reset_settings_url' => $crud->getUrl(['razsettings' => 1]),
                'ajax_options' => $ajaxOptions,
                'image_url' => $options['image_url'],
                'suffix' => $crud->getSessionName(),
                'use_bootstrap' => $options['use_bootstrap'],
                'close_div_class' => $options['modal_close_div_class'],
                'overlay_service' => $this->crudHelper->getOverlayService(),
                'display_button' => $crud->getDisplayResults(),
                'div_id_list' => $crud->getDivIdList(),
            ]
        );
    }

    /**
     * Twig function: "crud_search_form".
     *
     * @see CrudHelper:searchFormTag
     */
    public function searchForm(Crud $crud, $ajaxOptions = [], $htmlOptions = [])
    {
        return $this->crudHelper->searchFormTag($crud, $ajaxOptions, $htmlOptions);
    }

    /**
     * Twig function: "crud_search_reset".
     *
     * @see CrudHelper:searchResetButton
     */
    public function searchReset(Crud $crud, $options = [], $ajaxOptions = [], $htmlOptions = [])
    {
        return $this->crudHelper->searchResetButton($crud, $options, $ajaxOptions, $htmlOptions);
    }

    /**
     * Twig function: "crud_declare_modal".
     *
     * @see CrudHelper:declareModal
     */
    public function declareModal($modalId, $options = [])
    {
        return $this->crudHelper->declareModal($modalId, $options);
    }

    /**
     * Twig function: "crud_remote_modal".
     *
     * @see CrudHelper:remoteModal
     */
    public function remoteModal($modalId, $url, $options = [], $ajaxOptions = [])
    {
        return $this->crudHelper->remoteModal($modalId, $url, $options, $ajaxOptions);
    }

    /**
     * Twig function: "crud_form_modal".
     *
     * @see CrudHelper:formModal
     */
    public function formModal($modalId, $form, $ajaxOptions = [], $htmlOptions = [])
    {
        return $this->crudHelper->formModal($modalId, $form, $ajaxOptions, $htmlOptions);
    }

    public function formStartAjax(FormView $formView, array $options = []): string
    {
        $autoClass = 'ec-crud-ajax-form-auto';
        if (isset($options['auto_class']) && null !== isset($options['auto_class'])) {
            $autoClass = $options['auto_class'];
            unset($options['auto_class']);
        }
        if (isset($options['attr']['class'])) {
            $options['attr']['class'] = sprintf('%s %s', $autoClass, $options['attr']['class']);
        } else {
            $options['attr']['class'] = $autoClass;
        }

        if (isset($options['ajax_options'])) {
            $this->validateAjaxOptions($options['ajax_options']);
            $options['attr'] = array_merge(
                $options['attr'],
                $this->getAjaxAttributes($options['ajax_options'])
            );
            unset($options['ajax_options']);
        }

        return $this->formRenderer->renderBlock($formView, 'form_start', $options);
    }

    public function ajaxAttributes(Environment $environment, array $ajaxOptions): string
    {
        $this->validateAjaxOptions($ajaxOptions);
        $attributes = $this->getAjaxAttributes($ajaxOptions);
        if (0 === \count($attributes)) {
            return '';
        }

        return $this->renderBlock($environment, $this->theme, 'attributes', [
            'attr' => $attributes,
        ]);
    }

    protected function validateAjaxOptions(array $options, array $requiredOptions = []): array
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'url' => null,
            'update' => null,
            'update_mode' => null,
            'on_before_send' => null,
            'on_success' => null,
            'on_error' => null,
            'on_complete' => null,
            'data_type' => null,
            'method' => null,
            'data' => null,
            'cache' => null,
            'options' => null,
        ]);
        $resolver->setRequired($requiredOptions);

        return $resolver->resolve($options);
    }

    protected function getAjaxAttributes(array $options): array
    {
        $attributes = [];
        foreach ($options as $optionName => $optionValue) {
            if (null === $optionValue) {
                continue;
            }

            if (\is_bool($optionValue)) {
                $optionValue = ($optionValue) ? 'true' : 'false';
            } elseif (\is_array($optionValue)) {
                $optionValue = json_encode($optionValue);
            }

            $optionName = str_replace('_', '-', $optionName);
            $attributes['data-ec-crud-ajax-'.$optionName] = (string) $optionValue;
        }

        return $attributes;
    }

    protected function renderBlock(Environment $environment, string $templateName, string $blockName, array $parameters = []): ?string
    {
        $template = $environment->load($templateName);

        ob_start();
        $template->displayBlock($blockName, $parameters);

        return ob_get_clean();
    }
}

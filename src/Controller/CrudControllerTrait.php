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

namespace Ecommit\CrudBundle\Controller;

use Ecommit\CrudBundle\Crud\Crud;
use Ecommit\CrudBundle\Crud\CrudFactory;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

trait CrudControllerTrait
{
    protected $cm;

    /**
     * @param $sessionName
     *
     * @return Crud
     */
    protected function createCrud($sessionName)
    {
        return $this->container->get('ecommit_crud.factory')->create($sessionName);
    }

    public function autoListAction()
    {
        $data = $this->prepareList();

        return $this->renderCrud($this->getTemplateName('index'), array_merge($data, ['crud' => $this->cm]));
    }

    protected function prepareList()
    {
        $this->cm = $this->configCrud();
        $this->beforeBuildQuery();
        $this->cm->buildQuery();
        $this->afterBuildQuery();
        $data = $this->addDataAfterBuildQuery();
        $this->cm->clearTemplate();

        return $data;
    }

    /**
     * Configures and returns the CRUD.
     *
     * @return Crud
     */
    abstract protected function configCrud();

    /**
     * @return array
     */
    protected function addDataAfterBuildQuery()
    {
        return [];
    }

    protected function beforeBuildQuery(): void
    {
    }

    protected function afterBuildQuery(): void
    {
    }

    /**
     * Returns template for action.
     *
     * @param string $action Action
     *
     * @return string
     */
    abstract protected function getTemplateName($action);

    public function autoAjaxListAction()
    {
        $masterRequest = $this->get('request_stack')->getMasterRequest();
        if (!$masterRequest->isXmlHttpRequest()) {
            throw new NotFoundHttpException('Ajax is required');
        }
        $data = $this->prepareList();

        return $this->renderCrud($this->getTemplateName('list'), array_merge($data, ['crud' => $this->cm]));
    }

    public function autoAjaxSearchAction()
    {
        $masterRequest = $this->get('request_stack')->getMasterRequest();
        if (!$masterRequest->isXmlHttpRequest()) {
            throw new NotFoundHttpException('Ajax is required');
        }
        $data = $this->processSearch();
        $renderSearch = $this->renderCrudView(
            $this->getTemplateName('search'),
            array_merge($data, ['crud' => $this->cm])
        );
        $renderList = $this->renderCrudView($this->getTemplateName('list'), array_merge($data, ['crud' => $this->cm]));

        return $this->renderCrud(
            '@EcommitCrud/Crud/double_search.html.twig',
            [
                'id_search' => $this->cm->getDivIdSearch(),
                'id_list' => $this->cm->getDivIdList(),
                'render_search' => $renderSearch,
                'render_list' => $renderList,
            ]
        );
    }

    protected function processSearch()
    {
        $this->cm = $this->configCrud();
        $this->cm->processForm();
        $this->beforeBuildQuery();
        $this->cm->buildQuery();
        $this->afterBuildQuery();
        $data = $this->addDataAfterBuildQuery();
        $this->cm->clearTemplate();

        return $data;
    }

    protected function renderCrudView(string $view, array $parameters = []): string
    {
        return $this->container->get('twig')->render($view, $parameters);
    }

    protected function renderCrud(string $view, array $parameters = [], Response $response = null): Response
    {
        $content = $this->container->get('twig')->render($view, $parameters);

        if (null === $response) {
            $response = new Response();
        }

        $response->setContent($content);

        return $response;
    }

    protected static function getCrudRequiredServices()
    {
        return [
            'ecommit_crud.factory' => CrudFactory::class,
            'twig' => Environment::class,
            'request_stack' => RequestStack::class,
        ];
    }
}

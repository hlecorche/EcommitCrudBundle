<?php

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
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AbstractCrudController extends Controller
{
    protected $cm;

    /**
     * @param $sessionName
     * @return Crud
     */
    protected function createCrud($sessionName)
    {
        return $this->get('ecommit_crud.factory')->create($sessionName);
    }

    public function autoListAction()
    {
        $data = $this->prepareList();

        return $this->render($this->getPathView('index'), \array_merge($data, array('crud' => $this->cm)));
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
     * Configures and returns the CRUD
     *
     * @return Crud
     */
    abstract protected function configCrud();

    /**
     *
     * @return array
     */
    protected function addDataAfterBuildQuery()
    {
        return array();
    }

    protected function beforeBuildQuery()
    {
    }

    protected function afterBuildQuery()
    {
    }

    /**
     * Returns the path of the template given
     *
     * @param string $name Template name
     * @return string
     */
    protected function getPathView($name)
    {
        if (preg_match('/^(?P<vendor>\w+)\\\(?P<bundle>\w+)\\\Controller\\\(?P<controller>\w+)Controller$/', get_class($this), $matches)) {
            return sprintf('%s%s:%s:%s.html.twig', $matches['vendor'], $matches['bundle'], $matches['controller'], $name);
        } elseif (preg_match('/^(?P<vendor>\w+)\\\(?P<bundle>\w+)\\\Controller\\\(?P<dir>\w+)\\\(?P<controller>\w+)Controller$/', get_class($this), $matches)) {
            return sprintf('%s%s:%s/%s:%s.html.twig', $matches['vendor'], $matches['bundle'], $matches['dir'], $matches['controller'], $name);
        } elseif (preg_match('/^AppBundle\\\Controller\\\(?P<controller>\w+)Controller$/', get_class($this), $matches)) {
            return sprintf('AppBundle:%s:%s.html.twig', $matches['controller'], $name);
        } elseif (preg_match('/^AppBundle\\\Controller\\\(?P<dir>\w+)\\\(?P<controller>\w+)Controller$/', get_class($this), $matches)) {
            return sprintf('AppBundle:%s/%s:%s.html.twig', $matches['dir'], $matches['controller'], $name);
        }

        new \Exception('getPathView: Bad structure');
    }

    public function autoAjaxListAction()
    {
        $masterRequest = $this->get('request_stack')->getMasterRequest();
        if (!$masterRequest->isXmlHttpRequest()) {
            throw new NotFoundHttpException('Ajax is required');
        }
        $data = $this->prepareList();

        return $this->render($this->getPathView('list'), \array_merge($data, array('crud' => $this->cm)));
    }

    public function autoAjaxSearchAction()
    {
        $masterRequest = $this->get('request_stack')->getMasterRequest();
        if (!$masterRequest->isXmlHttpRequest()) {
            throw new NotFoundHttpException('Ajax is required');
        }
        $data = $this->processSearch();
        $renderSearch = $this->renderView(
            $this->getPathView('search'),
            \array_merge($data, array('crud' => $this->cm))
        );
        $renderList = $this->renderView($this->getPathView('list'), \array_merge($data, array('crud' => $this->cm)));

        return $this->render(
            'EcommitCrudBundle:Crud:double_search.html.twig',
            array(
                'id_search' => $this->cm->getDivIdSearch(),
                'id_list' => $this->cm->getDivIdList(),
                'render_search' => $renderSearch,
                'render_list' => $renderList
            )
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
}

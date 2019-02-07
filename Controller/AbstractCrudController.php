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
use Ecommit\CrudBundle\Crud\CrudFactory;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RequestStack;
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
        return $this->get(CrudFactory::class)->create($sessionName);
    }

    public function autoListAction()
    {
        $data = $this->prepareList();

        return $this->render($this->getTemplateName('index'), \array_merge($data, array('crud' => $this->cm)));
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
     * Returns template for action
     *
     * @param string $action Action
     * @return string
     */
    protected function getTemplateName($action)
    {
        trigger_error('The "getTemplateName" must be overrided. This method will soon be abstract.', E_USER_DEPRECATED);

        return $this->getPathView($action);
    }

    /**
     * Returns the path of the template given
     *
     * @param string $name Template name
     * @return string
     * @deprecated Deprecated since version 2.4. Override getTemplateName method instead.
     */
    protected function getPathView($name)
    {
        trigger_error('The "getPathView" method is deprecated since version 2.4. Override "getTemplateName" method instead.', E_USER_DEPRECATED);

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
        //Legacy mode
        //Remove comments when this class will extend AbstractController (Contoller is deprecated and RequestStack service is private)
        //Change Controller to AbstractController breaks Backward Compatibility
        //$masterRequest = $this->get(RequestStack::class)->getMasterRequest();
        //if (!$masterRequest->isXmlHttpRequest()) {
        //    throw new NotFoundHttpException('Ajax is required');
        //}
        $data = $this->prepareList();

        return $this->render($this->getTemplateName('list'), \array_merge($data, array('crud' => $this->cm)));
    }

    public function autoAjaxSearchAction()
    {
        //Legacy mode
        //Remove comments when this class will extend AbstractController (Contoller is deprecated and RequestStack service is private)
        //Change Controller to AbstractController breaks Backward Compatibility
        //$masterRequest = $this->get(RequestStack::class)->getMasterRequest();
        //if (!$masterRequest->isXmlHttpRequest()) {
        //    throw new NotFoundHttpException('Ajax is required');
        //}
        $data = $this->processSearch();
        $renderSearch = $this->renderView(
            $this->getTemplateName('search'),
            \array_merge($data, array('crud' => $this->cm))
        );
        $renderList = $this->renderView($this->getTemplateName('list'), \array_merge($data, array('crud' => $this->cm)));

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

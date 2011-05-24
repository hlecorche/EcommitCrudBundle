<?php

namespace Ecommit\CrudBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class CrudAbstractController extends Controller
{
    protected $cm;

    /**
     * Configures and returns the CRUD
     * 
     * @return CrudManager
     */
    abstract protected function configCrud();
    
    /**
     * Returns the path of the template given
     * 
     * @param string $name Template name 
     * @return string
     */
    abstract protected function getPathView($name);


    public function autoListAction()
    {
        $this->prepareList();
        return $this->render($this->getPathView('index'), array('crud' => $this->cm));
    }
    
    public function autoAjaxListAction()
    {
        $request = $this->get('request');
        if(!$request->isXmlHttpRequest())
        {
            throw new NotFoundHttpException('Ajax is required');
        }
        $this->prepareList();
        return $this->render($this->getPathView('list'), array('crud' => $this->cm));
    }
    
    public function autoAjaxSearchAction()
    {
        $request = $this->get('request');
        if(!$request->isXmlHttpRequest())
        {
            throw new NotFoundHttpException('Ajax is required');
        }
        $this->processSearch();
        $render_search = $this->renderView($this->getPathView('search'), array('crud' => $this->cm));
        $render_list = $this->renderView($this->getPathView('list'), array('crud' => $this->cm));
        return $this->render('EcommitCrudBundle:Crud:double_search.html.twig', array('render_search' => $render_search,
            'render_list' => $render_list));
    }
    
    
    protected function prepareList()
    {
        $this->cm = $this->configCrud();
        $this->cm->buildQuery();
        $this->cm->clearTemplate();
    }
    
    protected function processSearch()
    {
        $this->cm = $this->configCrud();
        $this->cm->processForm();
        $this->cm->buildQuery();
        $this->cm->clearTemplate();
    }
}

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
        $data = $this->prepareList();
        return $this->render($this->getPathView('index'), \array_merge($data, array('crud' => $this->cm)));
    }
    
    public function autoAjaxListAction()
    {
        $request = $this->get('request');
        if(!$request->isXmlHttpRequest())
        {
            throw new NotFoundHttpException('Ajax is required');
        }
        $data = $this->prepareList();
        return $this->render($this->getPathView('list'), \array_merge($data, array('crud' => $this->cm)));
    }
    
    public function autoAjaxSearchAction()
    {
        $request = $this->get('request');
        if(!$request->isXmlHttpRequest())
        {
            throw new NotFoundHttpException('Ajax is required');
        }
        $data = $this->processSearch();
        $render_search = $this->renderView($this->getPathView('search'), \array_merge($data, array('crud' => $this->cm)));
        $render_list = $this->renderView($this->getPathView('list'), \array_merge($data, array('crud' => $this->cm)));
        return $this->render('EcommitCrudBundle:Crud:double_search.html.twig', array('render_search' => $render_search,
            'render_list' => $render_list));
    }
    
    
    protected function prepareList()
    {
        $this->cm = $this->configCrud();
        $this->cm->buildQuery();
		$data = $this->addDataAfterBuildQuery();
        $this->cm->clearTemplate();
		return $data;
    }
    
    protected function processSearch()
    {
        $this->cm = $this->configCrud();
        $this->cm->processForm();
        $this->cm->buildQuery();
		$data = $this->addDataAfterBuildQuery();
        $this->cm->clearTemplate();
		return $data;
    }
	
	/**
	 *
	 * @return array 
	 */
	protected function addDataAfterBuildQuery()
	{
		return array();
	}
}

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

namespace Ecommit\CrudBundle\Tests\Functional\App\Controller;

use Ecommit\CrudBundle\Controller\AbstractCrudController;
use Ecommit\CrudBundle\Crud\Crud;
use Ecommit\CrudBundle\Tests\Functional\App\Entity\TestUser;
use Ecommit\CrudBundle\Tests\Functional\App\Form\Searcher\UserSearcher;

class UserController extends AbstractCrudController
{
    protected function configCrud()
    {
        $em = $this->getDoctrine()->getManager();

        $queryBuilder = $em->getRepository(TestUser::class)
            ->createQueryBuilder('u')
            ->select('u');

        $crud = $this->createCrud('user');
        $crud->addColumn('username', 'u.username', 'username', ['default_displayed' => false])
            ->addColumn('firstName', 'u.firstName', 'first_name')
            ->addColumn('lastName', 'u.lastName', 'last_name')
            ->setQueryBuilder($queryBuilder)
            ->setAvailableResultsPerPage([5, 5, 10, 50], 5)
            ->setDefaultSort('firstName', Crud::ASC)
            ->createSearcherForm(new UserSearcher())
            ->setRoute('user_ajax_list')
            ->setSearchRoute('user_ajax_search')
            ->setPersistentSettings(true)
            ->init();

        return $crud;
    }

    protected function getTemplateName($action)
    {
        return sprintf('user/%s.html.twig', $action);
    }

    public function listAction()
    {
        return $this->autoListAction();
    }

    public function ajaxListAction()
    {
        return $this->autoAjaxListAction();
    }

    public function ajaxSearchAction()
    {
        return $this->autoAjaxSearchAction();
    }
}

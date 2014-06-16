<?php
/**
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\Crud;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\SecurityContext;

class CrudFactory
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * @var FormFactory
     */
    protected $formFactory;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var SecurityContext
     */
    protected $securityContext;

    public function __construct(
        Router $router,
        FormFactory $formFactory,
        RequestStack $requestStack,
        Registry $registry,
        SecurityContext $securityContext
    ) {
        $this->router = $router;
        $this->formFactory = $formFactory;
        $this->requestStack = $requestStack;
        $this->registry = $registry;
        $this->securityContext = $securityContext;
    }

    /**
     * @param $session_name
     * @return Crud
     */
    public function create($session_name)
    {
        return new Crud(
            $session_name,
            $this->router,
            $this->formFactory,
            $this->requestStack->getCurrentRequest(),
            $this->registry,
            $this->securityContext->getToken()->getUser()
        );
    }
} 
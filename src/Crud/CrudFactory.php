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

namespace Ecommit\CrudBundle\Crud;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CrudFactory
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var array
     */
    protected $templateConfiguration;

    public function __construct(
        RouterInterface $router,
        FormFactoryInterface $formFactory,
        RequestStack $requestStack,
        ManagerRegistry $registry,
        TokenStorageInterface $tokenStorage,
        array $templateConfiguration
    ) {
        $this->router = $router;
        $this->formFactory = $formFactory;
        $this->requestStack = $requestStack;
        $this->registry = $registry;
        $this->tokenStorage = $tokenStorage;
        $this->templateConfiguration = $templateConfiguration;
    }

    /**
     * @param $sessionName
     *
     * @return Crud
     */
    public function create($sessionName)
    {
        return new Crud(
            $sessionName,
            $this->router,
            $this->formFactory,
            $this->requestStack->getCurrentRequest(),
            $this->registry,
            $this->tokenStorage->getToken()->getUser(),
            $this->templateConfiguration
        );
    }
}

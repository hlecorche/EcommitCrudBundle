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
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

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
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var array
     */
    protected $templateConfiguration;

    public function __construct(
        Router $router,
        FormFactory $formFactory,
        RequestStack $requestStack,
        Registry $registry,
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
     * @return Crud
     */
    public function create($sessionName)
    {
        if (null === $this->registry) {
            throw new \Exception('Doctrine is required.');
        }

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

<?php

namespace App\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class RequestListener
{
    protected $router;
    protected $logger;
    protected $entityManager;
    protected $tokenStorage;
    protected $authorizationChecker;

    public function __construct(
        UrlGeneratorInterface $router,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->router = $router;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (strstr($event->getRequest()->getPathInfo(), "disclaimer")) {
            return;
        }

        $userTimeSpent = $event->getRequest()->getSession()->get('user_time_spent');
        if ($this->tokenStorage->getToken() != null) {
            $user = $this->tokenStorage->getToken()->getUser();
            if ($userTimeSpent == null) {
                $event->getRequest()->getSession()->set('user_time_spent', time());
            } else {
                // Update the database every 10 seconds min
                if (time() > $userTimeSpent + 10 && $user != null && $this->authorizationChecker->isGranted('ROLE_USER')) {
                    $user->setTimeSpent($user->getTimeSpent() + (time() - $userTimeSpent));
                    $this->entityManager->persist($user);
                    $this->entityManager->flush();
                    // Put a new timestamp
                    $event->getRequest()->getSession()->set('user_time_spent', time());
                }
            }
        }

        // Check disclaimer and check googlebot agent
        if ($event->getRequest()->query->get('disclaimer') == 1 || strstr(strtolower($event->getRequest()->headers->get('User-Agent')),
                "google") != false) {
            $event->getRequest()->getSession()->set('disclaimer', true);
        } else {
            if ($event->getRequest()->getSession()->get('disclaimer') != true && $_ENV['USE_DISCLAIMER'] == 1) {
                $event->getRequest()->getSession()->set('final_url', $event->getRequest()->getUri());
                $event->setResponse(new RedirectResponse($this->router->generate('disclaimer')));
            }
        }


    }
}
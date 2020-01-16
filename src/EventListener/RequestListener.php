<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RequestListener
{
    protected $router;

    public function __construct(UrlGeneratorInterface $router)
    {
        $this->router = $router;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (strstr($event->getRequest()->getPathInfo(), "disclaimer")) {
            return;
        }

        // Check disclaimer and check googlebot agent
        if ($event->getRequest()->query->get('disclaimer') == 1 || strstr(strtolower($event->getRequest()->headers->get('User-Agent')),
                "google") != false) {
            $event->getRequest()->getSession()->set('disclaimer', true);
        } else {
            if ($event->getRequest()->getSession()->get('disclaimer') != true && $_ENV['USE_DISCLAIMER'] == 1) {
                $event->setResponse(new RedirectResponse($this->router->generate('disclaimer')));
            }
        }


    }
}
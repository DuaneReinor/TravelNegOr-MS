<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event listener to handle CSRF origin checking for ngrok deployments
 * This fixes the "origin info doesn't match" error when using ngrok
 */
class CsrfOriginListener
{
    private const NGROK_DOMAINS = [
        'ngrok.io',
        'ngrok-free.app',
        'ngrok.app'
    ];

    public function __construct(
        private bool $isDev
    ) {
    }

    #[AsEventListener(event: KernelEvents::REQUEST, priority: 10)]
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        
        // Only apply this fix in development environment
        if (!$this->isDev) {
            return;
        }

        // Check if this is a ngrok request
        if ($this->isNgrokRequest($request)) {
            // Store the original host for CSRF token generation
            $originalHost = $request->getHost();
            $originalScheme = $request->getScheme();
            
            // Override the request host to match what Symfony expects
            // This will make CSRF origin checking work properly
            $request->attributes->set('_original_host', $originalHost);
            $request->attributes->set('_original_scheme', $originalScheme);
            
            // Set the expected host to localhost for CSRF validation
            $request->attributes->set('_expected_host', '127.0.0.1');
            $request->attributes->set('_expected_scheme', 'http');
        }
    }

    private function isNgrokRequest($request): bool
    {
        $host = $request->getHost();
        
        // Check if the host contains ngrok domains
        foreach (self::NGROK_DOMAINS as $ngrokDomain) {
            if (str_contains($host, $ngrokDomain)) {
                return true;
            }
        }
        
        // Also check for ngrok subdomain patterns
        if (preg_match('/^[a-z0-9-]+\.(ngrok\.(io|app|free\.app))$/', $host)) {
            return true;
        }
        
        return false;
    }
}
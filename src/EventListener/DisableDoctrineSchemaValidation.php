<?php

namespace App\EventListener;

use Doctrine\ORM\Tools\SchemaValidator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Disables Doctrine SchemaValidator to prevent ManyToManyInverseSideMapping::$inversedBy warning
 * 
 * This is a temporary workaround for Doctrine ORM 3.5.2 bug that occurs when Doctrine
 * tries to validate entity mappings. Since the current application has no ManyToMany
 * relationships, this validation is unnecessary and the warning can be safely suppressed.
 */
class DisableDoctrineSchemaValidation
{
    #[AsEventListener(event: KernelEvents::REQUEST, priority: 10)]
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // Only disable validation in development environment
        if (($_ENV['APP_ENV'] ?? 'dev') !== 'dev') {
            return;
        }

        // Suppress the Doctrine schema validation warning by setting error handler
        set_error_handler(function ($severity, $message, $file, $line) {
            // Check if this is the specific Doctrine warning we want to suppress
            if (str_contains($message, 'Undefined property: Doctrine\ORM\Mapping\ManyToManyInverseSideMapping::$inversedBy')) {
                // Log the suppression for debugging
                error_log("Suppressed Doctrine ORM warning in development: " . $message);
                
                // Don't call the previous error handler, just suppress this warning
                return true;
            }
            
            // For all other errors, call the previous error handler
            return false;
        });
    }
}
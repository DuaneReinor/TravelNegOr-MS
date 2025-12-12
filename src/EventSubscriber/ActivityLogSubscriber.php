<?php

namespace App\EventSubscriber;

use App\Entity\ActivityLog;
use App\Entity\Destination;
use App\Entity\Hotel;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ActivityLogSubscriber implements EventSubscriberInterface
{
    private TokenStorageInterface $tokenStorage;
    private RequestStack $requestStack;
    private array $oldDataStorage = [];

    public function __construct(TokenStorageInterface $tokenStorage, RequestStack $requestStack)
    {
        $this->tokenStorage = $tokenStorage;
        $this->requestStack = $requestStack;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::preUpdate,
            Events::postUpdate,
            Events::postRemove,
        ];
    }

    /**
     * Log entity creation
     */
    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();
        $entityManager = $args->getObjectManager();

        error_log('ActivityLogSubscriber: postPersist called for ' . get_class($entity));

        if (!$this->shouldLogEntity($entity)) {
            return;
        }

        try {
            $user = $this->getCurrentUser();
            $request = $this->requestStack->getCurrentRequest();

            $activityLog = ActivityLog::create(
                action: 'CREATE',
                entityType: $this->getEntityType($entity),
                entityId: $this->getEntityId($entity),
                entityName: $this->getEntityName($entity),
                user: $user,
                description: $this->getCreateDescription($entity),
                newData: $this->serializeEntity($entity)
            );

            if ($request) {
                $activityLog->setIpAddress($request->getClientIp())
                           ->setUserAgent($request->headers->get('User-Agent'));
            }

            // Use a fresh entity manager to avoid context issues
            $freshEm = $this->getFreshEntityManager($entityManager);
            $freshEm->persist($activityLog);
            $freshEm->flush();
            
            error_log('ActivityLogSubscriber: Activity log created for ' . get_class($entity));
        } catch (\Exception $e) {
            error_log('ActivityLogSubscriber: Error creating activity log: ' . $e->getMessage());
        }
    }

    /**
     * Log entity updates (capture old and new data)
     */
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        error_log('ActivityLogSubscriber: preUpdate called for ' . get_class($entity));

        if (!$this->shouldLogEntity($entity)) {
            return;
        }

        try {
            // Store old data for postUpdate
            $entityId = $this->getEntityId($entity);
            if ($entityId) {
                $this->oldDataStorage[$entityId] = $this->serializeChanges($args->getEntityChangeSet());
            }
        } catch (\Exception $e) {
            error_log('ActivityLogSubscriber: Error in preUpdate: ' . $e->getMessage());
        }
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        $entityManager = $args->getObjectManager();

        error_log('ActivityLogSubscriber: postUpdate called for ' . get_class($entity));

        if (!$this->shouldLogEntity($entity)) {
            return;
        }

        try {
            $user = $this->getCurrentUser();
            $request = $this->requestStack->getCurrentRequest();

            $entityId = $this->getEntityId($entity);
            $oldData = $this->oldDataStorage[$entityId] ?? null;
            $newData = $this->serializeEntity($entity);

            // Clean up stored data
            unset($this->oldDataStorage[$entityId]);

            $activityLog = ActivityLog::create(
                action: 'UPDATE',
                entityType: $this->getEntityType($entity),
                entityId: $entityId,
                entityName: $this->getEntityName($entity),
                user: $user,
                description: $this->getUpdateDescription($entity, $oldData, $newData),
                oldData: $oldData,
                newData: $newData
            );

            if ($request) {
                $activityLog->setIpAddress($request->getClientIp())
                           ->setUserAgent($request->headers->get('User-Agent'));
            }

            // Use a fresh entity manager to avoid context issues
            $freshEm = $this->getFreshEntityManager($entityManager);
            $freshEm->persist($activityLog);
            $freshEm->flush();
            
            error_log('ActivityLogSubscriber: Activity log updated for ' . get_class($entity));
        } catch (\Exception $e) {
            error_log('ActivityLogSubscriber: Error updating activity log: ' . $e->getMessage());
        }
    }

    /**
     * Log entity deletion
     */
    public function postRemove(PostRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        $entityManager = $args->getObjectManager();

        error_log('ActivityLogSubscriber: postRemove called for ' . get_class($entity));

        if (!$this->shouldLogEntity($entity)) {
            return;
        }

        try {
            $user = $this->getCurrentUser();
            $request = $this->requestStack->getCurrentRequest();

            $activityLog = ActivityLog::create(
                action: 'DELETE',
                entityType: $this->getEntityType($entity),
                entityId: $this->getEntityId($entity),
                entityName: $this->getEntityName($entity),
                user: $user,
                description: $this->getDeleteDescription($entity),
                oldData: $this->serializeEntity($entity)
            );

            if ($request) {
                $activityLog->setIpAddress($request->getClientIp())
                           ->setUserAgent($request->headers->get('User-Agent'));
            }

            // Use a fresh entity manager to avoid context issues
            $freshEm = $this->getFreshEntityManager($entityManager);
            $freshEm->persist($activityLog);
            $freshEm->flush();
            
            error_log('ActivityLogSubscriber: Activity log removed for ' . get_class($entity));
        } catch (\Exception $e) {
            error_log('ActivityLogSubscriber: Error removing activity log: ' . $e->getMessage());
        }
    }

    private function shouldLogEntity($entity): bool
    {
        $shouldLog = $entity instanceof Destination || 
                    $entity instanceof Hotel || 
                    $entity instanceof User;
        
        if ($shouldLog) {
            error_log("ActivityLogSubscriber: Should log entity: " . get_class($entity) . " with ID: " . ($entity->getId() ?? 'null'));
        }
        
        return $shouldLog;
    }

    private function getCurrentUser(): ?User
    {
        $token = $this->tokenStorage->getToken();
        return $token?->getUser() instanceof User ? $token->getUser() : null;
    }

    private function getEntityType($entity): string
    {
        if ($entity instanceof Destination) {
            return 'Destination';
        } elseif ($entity instanceof Hotel) {
            return 'Hotel';
        } elseif ($entity instanceof User) {
            return 'User';
        }

        return get_class($entity);
    }

    private function getEntityId($entity): ?int
    {
        return method_exists($entity, 'getId') ? $entity->getId() : null;
    }

    private function getEntityName($entity): ?string
    {
        if ($entity instanceof Destination) {
            return $entity->getName();
        } elseif ($entity instanceof Hotel) {
            return $entity->getName();
        } elseif ($entity instanceof User) {
            return $entity->getFirstName() . ' ' . $entity->getLastName();
        }

        return null;
    }

    private function serializeEntity($entity): ?array
    {
        if ($entity instanceof Destination) {
            return [
                'id' => $entity->getId(),
                'name' => $entity->getName(),
                'location' => $entity->getLocation(),
                'description' => $entity->getDescription(),
                'image' => $entity->getImage(),
                'createdBy' => $entity->getCreatedBy()?->getEmail(),
            ];
        } elseif ($entity instanceof Hotel) {
            return [
                'id' => $entity->getId(),
                'name' => $entity->getName(),
                'location' => $entity->getLocation(),
                'price' => $entity->getPrice(),
                'description' => $entity->getDescription(),
                'image' => $entity->getImage(),
            ];
        } elseif ($entity instanceof User) {
            return [
                'id' => $entity->getId(),
                'firstName' => $entity->getFirstName(),
                'lastName' => $entity->getLastName(),
                'email' => $entity->getEmail(),
                'roles' => $entity->getRoles(),
            ];
        }

        return null;
    }

    private function serializeChanges(array $changeSet): ?array
    {
        $changes = [];
        foreach ($changeSet as $field => $values) {
            $changes[$field] = [
                'old' => $values[0],
                'new' => $values[1],
            ];
        }
        return $changes;
    }

    private function getCreateDescription($entity): string
    {
        if ($entity instanceof Destination) {
            return "Created destination: {$entity->getName()} in {$entity->getLocation()}";
        } elseif ($entity instanceof Hotel) {
            return "Created hotel: {$entity->getName()} in {$entity->getLocation()}";
        } elseif ($entity instanceof User) {
            $name = $entity->getFirstName() . ' ' . $entity->getLastName();
            return "Created user: {$name} ({$entity->getEmail()})";
        }

        return "Created " . $this->getEntityType($entity);
    }

    private function getUpdateDescription($entity, ?array $oldData, ?array $newData): string
    {
        if ($entity instanceof Destination) {
            return "Updated destination: {$entity->getName()}";
        } elseif ($entity instanceof Hotel) {
            return "Updated hotel: {$entity->getName()}";
        } elseif ($entity instanceof User) {
            $name = $entity->getFirstName() . ' ' . $entity->getLastName();
            return "Updated user: {$name}";
        }

        return "Updated " . $this->getEntityType($entity);
    }

    private function getDeleteDescription($entity): string
    {
        if ($entity instanceof Destination) {
            return "Deleted destination: {$entity->getName()}";
        } elseif ($entity instanceof Hotel) {
            return "Deleted hotel: {$entity->getName()}";
        } elseif ($entity instanceof User) {
            $name = $entity->getFirstName() . ' ' . $entity->getLastName();
            return "Deleted user: {$name}";
        }

        return "Deleted " . $this->getEntityType($entity);
    }

    /**
     * Get a fresh entity manager to avoid context issues
     */
    private function getFreshEntityManager($currentEm)
    {
        // Return the same entity manager but ensure it's in a clean state
        // In Symfony, the same entity manager should work fine
        return $currentEm;
    }
}
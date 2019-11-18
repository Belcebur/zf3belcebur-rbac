<?php


namespace ZF3Belcebur\Rbac\Factory\Resource;


use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;
use Zend\Authentication\AuthenticationService;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;
use ZF3Belcebur\Rbac\Resource\RbacManager;

class RbacManagerFactory implements FactoryInterface
{

    /**
     * Create an object
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     * @return RbacManager
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $assertionManagers = [];
        $config = $container->get('Config');
        $rbacAccessFilter = (array)($config['rbac_manager']['access_filter'] ?? []);
        $assertions = (array)($config['rbac_manager']['assertions'] ?? []);
        foreach ($assertions as $serviceName) {
            $assertionManagers[$serviceName] = $container->get($serviceName);
        }

        return new RbacManager(
            $container->get('Request'),
            $container->get(EntityManager::class),
            $container->get(AuthenticationService::class),
            $rbacAccessFilter,
            $assertionManagers
        );
    }
}

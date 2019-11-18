<?php


namespace ZF3Belcebur\Rbac\Resource;


use Doctrine\ORM\EntityManager;
use RuntimeException;
use Zend\Authentication\AuthenticationService;
use Zend\Http\PhpEnvironment\Request;
use Zend\Permissions\Rbac\Rbac;
use ZF3Belcebur\Rbac\Entity\Permission as PermissionEntity;
use ZF3Belcebur\Rbac\Entity\Role as RoleEntity;
use ZF3Belcebur\Rbac\EntityTrait\UserRole;
use function array_key_exists;
use function array_map;
use function in_array;
use function is_callable;

class RbacManager
{

    public const ACCESS_GRANTED = 'ACCESS_GRANTED';

    public const AUTH_REQUIRED = 'AUTH_REQUIRED';

    public const ACCESS_DENIED = 'ACCESS_DENIED';


    /** @var EntityManager */
    private $entityManager;


    /** @var Rbac */
    private $rbac;

    /**
     * Auth service.
     * @var AuthenticationService
     */
    private $authService;

    /** @var array */
    private $rbacAccessFilter;

    /**
     * Assertion managers.
     * @var array
     */
    private $assertionManagers;

    /** @var Request */
    private $request;

    /**
     * Rbac constructor.
     * @param Request $request
     * @param EntityManager $entityManager
     * @param AuthenticationService $authService
     * @param array $rbacAccessFilter
     * @param array $assertionManagers
     */
    public function __construct(Request $request, EntityManager $entityManager, AuthenticationService $authService, array $rbacAccessFilter = [], array $assertionManagers = [])
    {
        $this->request = $request;
        $this->entityManager = $entityManager;
        $this->authService = $authService;
        $this->assertionManagers = $assertionManagers;
        $this->rbacAccessFilter = $rbacAccessFilter;
    }

    public function filterAccess($controllerName, $actionName): string
    {
        if ($this->rbac === null) {
            $this->init();
        }

        $mode = $this->getMode();
        $items = $this->getAccessListItems($controllerName);
        $identity = $this->getIdentity();

        foreach ($items as $item) {
            $actionList = (array)($item['actions'] ?? []);
            $actionList[] = null; //AbstractRestfullController
            $methodsList = array_map('strtoupper', (array)($item['methods'] ?? []));
            if (!$methodsList) {
                $methodsList[] = 'GET';
            } elseif (in_array('*', $methodsList, true)) {
                $methodsList = [
                    Request::METHOD_GET,
                    Request::METHOD_POST,
                    Request::METHOD_PUT,
                    Request::METHOD_DELETE,
                    Request::METHOD_PATCH,
                    Request::METHOD_CONNECT,
                    Request::METHOD_HEAD,
                    Request::METHOD_OPTIONS,
                    Request::METHOD_PROPFIND,
                    Request::METHOD_TRACE,
                ];
            }

            $allow = (array)($item['allow'] ?? []);

            if ((in_array('*', $actionList, true) || in_array($actionName, $actionList, true)) && in_array($this->request->getMethod(), $methodsList, true)) {
                if (in_array('*', $allow, true)) {
                    return self::ACCESS_GRANTED;
                }
                if (!$this->authService->hasIdentity()) {
                    // Only authenticated user is allowed to see the page.
                    return self::AUTH_REQUIRED;
                }

                if (in_array('@', $allow, true)) {
                    // Any authenticated user is allowed to see the page.
                    return self::ACCESS_GRANTED;
                }

                if (array_key_exists('@', $allow) && in_array($identity, $allow['@'], true)) {
                    return self::ACCESS_GRANTED;
                }

                if (array_key_exists('+', $allow)) {
                    foreach ($allow['+'] as $permission) {
                        if ($this->isGranted(null, $permission)) {
                            return self::ACCESS_GRANTED;
                        }
                    }
                }
                return self::ACCESS_DENIED;
            }
        }

        if ($mode === 'restrictive') {
            if (!$this->authService->hasIdentity()) {
                return self::AUTH_REQUIRED;
            }
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_GRANTED;
    }

    public function init(): void
    {
        $this->rbac = new Rbac();
        $this->configRoles();
        $this->configPermissions();

    }

    private function configRoles(): void
    {
        $this->rbac->setCreateMissingRoles(true);

        $roleRepo = $this->entityManager->getRepository(RoleEntity::class);
        /** @var RoleEntity $role */
        foreach ($roleRepo->findBy([]) as $role) {
            $this->rbac->addRole($role->getId(), $role->getParentRoles()->map(static function (RoleEntity $parentRole) {
                return $parentRole->getId();
            })->getValues());
        }
    }

    private function configPermissions(): void
    {
        $permissionRepo = $this->entityManager->getRepository(PermissionEntity::class);
        /** @var PermissionEntity $permission */
        foreach ($permissionRepo->findBy([]) as $permission) {
            /** @var RoleEntity $role */
            foreach ($permission->getRoles() as $role) {
                if ($this->rbac->hasRole($role->getId())) {
                    $this->rbac->getRole($role->getId())->addPermission($permission->getId());
                }
            }
        }
    }

    public function getMode(): string
    {
        $mode = $this->rbacAccessFilter['options']['mode'] ?? 'restrictive';

        if (!in_array($mode, [
            'restrictive',
            'permissive',
        ], true)) {
            return 'restrictive';
        }

        return $mode;
    }

    public function getAccessListItems(string $controllerName): array
    {
        return (array)($this->rbacAccessFilter['controllers'][$controllerName] ?? []);
    }

    /**
     * @return mixed|null
     */
    private function getIdentity()
    {
        $filterIdentity = $this->rbacAccessFilter['options']['filter_identity'];
        $identity = $this->authService->getIdentity();
        if (is_callable($filterIdentity)) {
            $identity = $filterIdentity($identity);
        }

        return $identity;
    }

    /**
     * @param $user
     * @param $permission
     * @param null $params
     * @return bool
     */
    public function isGranted($user, $permission, $params = null): bool
    {
        if ($this->rbac === null) {
            $this->init();
        }

        if ($user === null) {

            $user = $this->authService->getIdentity();
            if ($user === null) {
                return false;
            }

            if (!in_array(UserRole::class, (array)class_uses($user), true)) {
                throw new RuntimeException('There is no user use ' . UserRole::class);
            }

        }

        /** @var UserRole $user */
        $roles = $user->getRoles();
        foreach ($roles as $role) {
            if ($this->rbac->isGranted($role->getName(), $permission)) {

                if ($params === null) {
                    return true;
                }


                foreach ($this->assertionManagers as $assertionManager) {
                    if ($assertionManager->assert($this->rbac, $permission, $params)) {
                        return true;
                    }
                }
            }

            $parentRoles = $role->getParentRoles();
            /** @var RoleEntity $parentRole */
            foreach ($parentRoles as $parentRole) {
                if ($this->rbac->isGranted($parentRole->getId(), $permission)) {
                    return true;
                }
            }
        }

        return false;
    }


}

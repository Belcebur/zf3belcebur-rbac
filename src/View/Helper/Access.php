<?php


namespace ZF3Belcebur\Rbac\View\Helper;


use Zend\View\Helper\AbstractHelper;
use ZF3Belcebur\Rbac\Resource\RbacManager;

class Access extends AbstractHelper
{
    /** @var RbacManager */
    private $rbacManager;

    public function __construct(RbacManager $rbacManager)
    {
        $this->rbacManager = $rbacManager;
    }

    public function __invoke($permission, $params = [])
    {
        return $this->rbacManager->isGranted(null, $permission, $params);
    }
}

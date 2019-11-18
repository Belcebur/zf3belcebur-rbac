<?php


namespace ZF3Belcebur\Rbac\EntityTrait;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use ZF3Belcebur\Rbac\Entity\Role;
use function implode;

trait UserRole
{
    /**
     * @var ArrayCollection|Collection
     * @ORM\ManyToMany(targetEntity="Rbac\Entity\Role")
     * @ORM\JoinTable(name="user_role",
     *      joinColumns={@ORM\JoinColumn(name="user", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="role", referencedColumnName="id")}
     * )
     */
    protected $roles;

    /**
     * @return ArrayCollection|Collection
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @param ArrayCollection|Collection $roles
     * @return self
     */
    public function setRoles($roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * Returns the string of assigned role names.
     */
    public function getRolesAsString(): string
    {
        $roleList = [];
        /** @var Role $role */
        foreach ($this->roles as $role) {
            $roleList[$role->getId()];
        }
        return implode(', ', $roleList);
    }

    /**
     * Assigns a role to user.
     * @param Role $role
     * @return self
     */
    public function addRole(Role $role): self
    {
        $this->roles->add($role);
        return $this;
    }


}

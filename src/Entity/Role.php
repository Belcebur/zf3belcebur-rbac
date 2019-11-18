<?php


namespace ZF3Belcebur\Rbac\Entity;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ZF3Belcebur\DoctrineORMResources\EntityTrait\Timestamp;

/**
 * This class represents a role.
 * @ORM\Entity()
 * @ORM\Table(name="role")
 * @ORM\HasLifecycleCallbacks()
 */
class Role
{

    use Timestamp;

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="string", length=100, precision=0, scale=0, nullable=false, unique=true)
     * @ORM\GeneratedValue(strategy="NONE")
     */
    protected $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="description", type="string", length=250, precision=0, scale=0, nullable=true, unique=false)
     */
    protected $description;

    /**
     * @var ArrayCollection|Collection
     * @ORM\ManyToMany(targetEntity="Rbac\Entity\Role", inversedBy="childRoles")
     * @ORM\JoinTable(name="role_hierarchy",
     *      joinColumns={@ORM\JoinColumn(name="child_role", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="parent_role", referencedColumnName="id")}
     * )
     */
    protected $parentRoles;

    /**
     * @var ArrayCollection|Collection
     *
     * @ORM\ManyToMany(targetEntity="Rbac\Entity\Role", mappedBy="parentRoles")
     * @ORM\JoinTable(name="role_hierarchy",
     *      joinColumns={@ORM\JoinColumn(name="parent_role", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="child_role", referencedColumnName="id")}
     * )
     */
    protected $childRoles;

    /**
     * @var ArrayCollection|Collection
     * @ORM\ManyToMany(targetEntity="Rbac\Entity\Permission", inversedBy="roles")
     * @ORM\JoinTable(name="role_permission",
     *      joinColumns={@ORM\JoinColumn(name="role", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="permission", referencedColumnName="id")}
     * )
     */
    protected $permissions;

    /**
     * Constructor.
     * @param string $id
     * @param string|null $description
     */
    public function __construct(string $id, ?string $description = null)
    {
        $this->id = $id;
        $this->description = $description;
        $this->parentRoles = new ArrayCollection();
        $this->childRoles = new ArrayCollection();
        $this->permissions = new ArrayCollection();
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     * @return self
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return ArrayCollection|Collection
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * @param ArrayCollection|Collection $permissions
     * @return self
     */
    public function setPermissions($permissions): self
    {
        $this->permissions = $permissions;
        return $this;
    }

    public function addParent(Role $role): bool
    {
        if ($this->getId() === $role->getId()) {
            return false;
        }

        if (!$this->hasParent($role)) {
            $this->parentRoles->add($role);
            $role->getChildRoles()->add($this);
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Check if parent role exists
     * @param Role $role
     * @return bool
     */
    public function hasParent(Role $role): bool
    {
        if ($this->getParentRoles()->contains($role)) {
            return true;
        }

        return false;
    }

    /**
     * @return ArrayCollection|Collection
     */
    public function getParentRoles()
    {
        return $this->parentRoles;
    }

    /**
     * @param ArrayCollection|Collection $parentRoles
     * @return self
     */
    public function setParentRoles($parentRoles): self
    {
        $this->parentRoles = $parentRoles;
        return $this;
    }

    /**
     * @return ArrayCollection|Collection
     */
    public function getChildRoles()
    {
        return $this->childRoles;
    }

    /**
     * @param ArrayCollection|Collection $childRoles
     * @return self
     */
    public function setChildRoles($childRoles): self
    {
        $this->childRoles = $childRoles;
        return $this;
    }

    /**
     * Clear parent roles
     */
    public function clearParentRoles(): void
    {
        $this->parentRoles = new ArrayCollection();
    }
}

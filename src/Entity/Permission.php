<?php


namespace ZF3Belcebur\Rbac\Entity;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ZF3Belcebur\DoctrineORMResources\EntityTrait\Timestamp;

/**
 * This class represents a permission.
 * @ORM\Entity()
 * @ORM\Table(name="permission")
 * @ORM\HasLifecycleCallbacks()
 */
class Permission
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
     * @ORM\ManyToMany(targetEntity="Rbac\Entity\Role", mappedBy="permissions")
     * @ORM\JoinTable(name="role_permission",
     *     joinColumns={@ORM\JoinColumn(name="permission", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="role", referencedColumnName="id")}
     * )
     */
    protected $roles;

    /**
     * Constructor.
     * @param string $id
     * @param string|null $description
     */
    public function __construct(string $id, ?string $description = null)
    {
        $this->id = $id;
        $this->description = $description;
        $this->roles = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
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
     * @return string
     */
    public function __toString(): string
    {
        return $this->id;
    }


}

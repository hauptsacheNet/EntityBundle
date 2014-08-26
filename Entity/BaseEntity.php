<?php
/**
 * Created by PhpStorm.
 * User: marcopfeiffer
 * Date: 05.06.14
 * Time: 11:20
 */

namespace Hn\EntityBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;


/**
 * @ORM\MappedSuperclass()
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 */
abstract class BaseEntity
{
    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deletedAt;

    public function __construct()
    {
    }

    public function __clone()
    {
    }

    /**
     * Returns a readable class name
     *
     * @return string
     */
    public function getHumanClassName ()
    {
        $className = get_class($this);
        preg_match('/(?<=^|\W)\w+$/', $className, $matches);
        return !empty($matches) ? $matches[0] : $className;
    }

    /**
     * Returns a readable identifier like a name
     *
     * @return string
     */
    public function getHumanIdentifier()
    {
        return method_exists($this, 'getName') ? $this->getName() : (string) $this->getId();
    }

    /**
     * @return string
     */
    public final function __toString()
    {
        return get_class($this) . ':' . $this->getId();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt(\DateTime $createdAt = null)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $updatedAt
     */
    public function setUpdatedAt(\DateTime $updatedAt = null)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $deletedAt
     */
    public function setDeletedAt(\DateTime $deletedAt = null)
    {
        $this->deletedAt = $deletedAt;
    }

    /**
     * @return \DateTime
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }
} 
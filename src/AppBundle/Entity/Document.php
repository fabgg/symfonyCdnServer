<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Document
 *
 * @ORM\Table(name="document")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\DocumentRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Document
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createdAt", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updatedAt", type="datetime")
     */
    private $updatedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="fileName", type="string", length=255)
     */
    private $fileName;

    /**
     * @var string
     *
     * @ORM\Column(name="fileExtension", type="string", length=8, nullable=true)
     */
    private $fileExtension;

    /**
     * @var string
     *
     * @ORM\Column(name="fileMime", type="string", length=64, nullable=true)
     */
    private $fileMime;

    /**
     * @var int
     *
     * @ORM\Column(name="fileSize", type="integer", nullable=true)
     */
    private $fileSize;

    /**
     * @var array
     *
     * @ORM\Column(name="filePath", type="array")
     */
    private $filePath;

    /**
     * @var string
     *
     * @ORM\Column(name="fileThumb", type="string", length=255, nullable=true)
     */
    private $fileThumb;

    /**
     * @var string
     *
     * @ORM\Column(name="fileType", type="string", length=12, nullable=true)
     */
    private $fileType;

    /**
     * @var string
     *
     * @ORM\Column(name="filePreview", type="string", length=255, nullable=true)
     */
    private $filePreview;

    /**
     * @var int
     *
     * @ORM\Column(name="width", type="integer", nullable=true)
     */
    private $width;

    /**
     * @var int
     *
     * @ORM\Column(name="height", type="integer", nullable=true)
     */
    private $height;

    /**
     * @var string not stored in db jsut use one time on init
     */
    private $fileTmpPath;

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updateDate()
    {
        $this->setUpdatedAt(new \Datetime());

        if ($this->getCreatedAt() == null) {
            $this->setCreatedAt(new \DateTime());
        }
    }

    /**
     * Set fileTmpPath
     *
     * @param string $fileTmpPath
     *
     * @return Document
     */
    public function setFileTmpPath($fileTmpPath)
    {
        $this->fileTmpPath = $fileTmpPath;

        return $this;
    }

    /**
     * Get fileTmpPath
     *
     * @return string
     */
    public function getFileTmpPath()
    {
        return $this->fileTmpPath;
    }



    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Document
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return Document
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set fileName
     *
     * @param string $fileName
     *
     * @return Document
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * Get fileName
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Set fileExtension
     *
     * @param string $fileExtension
     *
     * @return Document
     */
    public function setFileExtension($fileExtension)
    {
        $this->fileExtension = $fileExtension;

        return $this;
    }

    /**
     * Get fileExtension
     *
     * @return string
     */
    public function getFileExtension()
    {
        return $this->fileExtension;
    }

    /**
     * Set fileMime
     *
     * @param string $fileMime
     *
     * @return Document
     */
    public function setFileMime($fileMime)
    {
        $this->fileMime = $fileMime;

        return $this;
    }

    /**
     * Get fileMime
     *
     * @return string
     */
    public function getFileMime()
    {
        return $this->fileMime;
    }

    /**
     * Set fileSize
     *
     * @param integer $fileSize
     *
     * @return Document
     */
    public function setFileSize($fileSize)
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    /**
     * Get fileSize
     *
     * @return int
     */
    public function getFileSize()
    {
        return $this->fileSize;
    }

    /**
     * Set filePath
     *
     * @param array $filePath
     *
     * @return Document
     */
    public function setFilePath($filePath)
    {
        $this->filePath = $filePath;

        return $this;
    }

    /**
     * Get filePath
     *
     * @return array
     */
    public function getFilePath()
    {
        return $this->filePath;
    }


    /**
     * Set fileThumb
     *
     * @param string $fileThumb
     *
     * @return Document
     */
    public function setFileThumb($fileThumb)
    {
        $this->fileThumb = $fileThumb;

        return $this;
    }

    /**
     * Get fileThumb
     *
     * @return string
     */
    public function getFileThumb()
    {
        return $this->fileThumb;
    }



    /**
     * Set fileType
     *
     * @param string $fileType
     *
     * @return Document
     */
    public function setFileType($fileType)
    {
        $this->fileType = $fileType;

        return $this;
    }

    /**
     * Get fileType
     *
     * @return string
     */
    public function getFileType()
    {
        return $this->fileType;
    }

    /**
     * Set filePreview
     *
     * @param string $filePreview
     *
     * @return Document
     */
    public function setFilePreview($filePreview)
    {
        $this->filePreview = $filePreview;

        return $this;
    }

    /**
     * Get filePreview
     *
     * @return string
     */
    public function getFilePreview()
    {
        return $this->filePreview;
    }

    /**
     * Set width
     *
     * @param integer $width
     *
     * @return Document
     */
    public function setWidth($width)
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Get width
     *
     * @return integer
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set height
     *
     * @param integer $height
     *
     * @return Document
     */
    public function setHeight($height)
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Get height
     *
     * @return integer
     */
    public function getHeight()
    {
        return $this->height;
    }
}

<?php
namespace OCA\DuplicateFinder\Db;

class FileInfo extends EEntity
{

    /** @var string */
    protected $owner;
    /** @var string */
    protected $path;
    /** @var string */
    protected $fileHash;
    /** @var string */
    protected $imageHash;
    /** @var integer */
    protected $updatedAt;
    /** @var integer */
    protected $nodeId;
    /** @var string */
    protected $mimetype;
    /** @var integer */
    protected $size;

    public function __construct(?string $path = null, ?string $owner = null)
    {
        $this->addInternalType('updatedAt', 'date');
        $this->addInternalProperty("nodId");
        $this->addInternalProperty("mimetype");
        $this->addInternalProperty("size");

        if (!is_null($path)) {
            $this->setPath($path);
        }
        if (!is_null($owner)) {
            $this->setOwner($owner);
        }
    }
}

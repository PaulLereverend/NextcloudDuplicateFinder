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

    public function __construct(?string $path = null, ?string $owner = null)
    {
        $this->addInternalType('updatedAt', 'date');

        if (!is_null($path)) {
            $this->setPath($path);
        }
        if (!is_null($owner)) {
            $this->setOwner($owner);
        }
    }
}

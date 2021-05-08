<?php
namespace OCA\DuplicateFinder\Db;

class FileDuplicate extends EEntity
{

    /** @var string */
    protected $type;
    /** @var string|null */
    protected $hash;
    /** @var array<string|FileInfo> */
    protected $files = [];

    public function __construct(?string $hash = null, string $type = "file_hash")
    {
        $this->addRelationalField("files");

        if (!is_null($hash)) {
            $this->setHash($hash);
        }
        $this->setType($type);
    }

    /**
     * @param int $id
     * @param string|FileInfo $value
     */
    public function addDuplicate(int $id, $value):void
    {
        if ($value instanceof FileInfo) {
            if (!isset($this->files[$id]) || $this->files[$id] !== $value->getOwner()) {
                $this->markRelationalFieldUpdated("files", $id, $value->getOwner());
            }
        } else {
            if (!isset($this->files[$id]) || $this->files[$id] !== $value) {
                $this->markRelationalFieldUpdated("files", $id, $value);
            }
        }
        $this->files[$id] = $value;
    }

    public function removeDuplicate(int $id):void
    {
        unset($this->files[$id]);
        $this->markRelationalFieldUpdated("files", $id);
    }

    public function clear():void
    {
        $this->files = [];
    }

  /**
     * @return array<string|FileInfo>
     */
    public function getFiles():array
    {
        return $this->files;
    }

    public function getCount(): int
    {
        return count($this->getFiles());
    }

    public function getCountForUser(string $user): int
    {
        $result = 0;
        foreach ($this->getFiles() as $u) {
            if ($u === $user) {
                $result += 1;
            }
        }
        return $result;
    }
}

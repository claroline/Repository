<?php

namespace Claroline\Model;

class Bundle
{
    private $name;
    private $authors;
    private $description;
    private $version;
    private $type;
    private $license;
    private $targetDir;
    private $basePath;
    private $requirements;

    public function __construct(
        $name, 
        $authors, 
        $description, 
        $version, 
        $type, 
        $license, 
        $targetDir, 
        $basePath,
        $requirements
    ) {
        $this->name = $name;
        $this->authors = $authors;
        $this->description = $description;
        $this->version = $version;
        $this->type = $type;
        $this->license = $license;
        $this->targetDir = $targetDir;
        $this->basePath = $basePath;
        $this->requirements = $requirements;
    }

    public function toArray()
    {
        return get_object_vars($this);
    }
}

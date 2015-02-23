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

    public function __construct($name, $authors, $description, $version, $type, $license) {
        $this->name = $name;
        $this->authors = $authors;
        $this->description = $description;
        $this->version = $version;
        $this->type = $type;
        $this->license = $license;
    }

    public function toArray()
    {
        return get_object_vars($this);
    }
}

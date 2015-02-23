<?php

namespace Claroline\Model;

class Bundle
{
    private $name;
    private $authors;
    private $description;
    private $version;
    private $type;

    public function __construct($name, $authors, $description, $version, $type) {
        $this->name = $name;
        $this->authors = $authors;
        $this->description = $description;
        $this->version = $version;
        $this->type = $type;
    }

    public function toArray()
    {
        return get_object_vars($this);
    }
}

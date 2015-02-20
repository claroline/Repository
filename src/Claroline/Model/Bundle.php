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
        $this->auhtors = $authors;
        $this->description = $description;
        $this->version = $version;
        $this->type = $type;
    }


    public function toArray()
    {
        return array(
            'name' => $this->name,
            'authors' => $this->authors,
            'description' => $this->description,
            'version' => $this->version,
            'type' => $this->type
        );
    }
}

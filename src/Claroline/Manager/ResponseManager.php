<?php

namespace Claroline\Manager;

class ResponseManager
{
    public function renderJson(array $data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    public function renderException($msg)
    {

    }

    public function downloadFile($filepath)
    {
        $fsize = filesize($filepath);
        $filename = pathinfo($filepath, PATHINFO_BASENAME);

        header("Content-Description: File Transfer");
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Content-length: $fsize");
        readfile ($filepath);
    }
}

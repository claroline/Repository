<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\FileSystem;

use Symfony\Component\Filesystem\Filesystem as Fs;

class FileSystem extends Fs
{
    public function rmdir($path, $recursive = false)
    {
        if (is_dir($path)) {
            if (!$recursive) {
                rmdir($path);
            } else {
                $this->recursiveRemoveDirectory($path);
            }
        }
    }

    public function rmDirContent($path, $recursive = false)
    {
        $iterator = new \DirectoryIterator($path);

        foreach ($iterator as $el) {
            if ($el->isDir()) $this->rmdir($el->getRealPath(), $recursive);
            if ($el->isFile()) $this->remove($el->getRealPath());
        }
    }

    //override not supported yet
    public function copyDir($path, $target, $originalPath = '', $originalTarget = '')
    {
        $iterator = new \DirectoryIterator($path);
        if ($originalPath === '') $originalPath = $path;
        if ($originalTarget === '') $originalTarget = $target;

        foreach ($iterator as $el) {
            if (!$el->isDot()) {
                $parts = explode($originalPath, $el->getRealPath());
                $basePath = $parts[1];
                $newPath = $originalTarget . $basePath;

                if ($el->isDir()) {
                    $this->mkdir($newPath);
                    $this->copyDir($el->getRealPath(), $newPath, $originalPath, $originalTarget);
                } else if ($el->isFile()){
                    $this->copy($el->getRealPath(), $newPath);
                }
            }
        }
    }

    private function recursiveRemoveDirectory($dir)
    {
        $it = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($files as $file) {
            if ($file->isFile()) {
                unlink($file->getRealPath());
            } else {
                rmdir($file->getRealPath());
            }
        }

        if (is_dir($dir)) {
            rmdir($dir);
        } else {
            unlink($dir);
        }
    }

    /*
     * http://www.php.net/manual/en/function.realpath.php
     */
    public function relativePath($from, $to, $ps = DIRECTORY_SEPARATOR)
    {
        $arFrom = explode($ps, rtrim($from, $ps));
        $arTo = explode($ps, rtrim($to, $ps));

        while (count($arFrom) && count($arTo) && ($arFrom[0] == $arTo[0])) {
            array_shift($arFrom);
            array_shift($arTo);
        }

        return str_pad("", count($arFrom) * 3, '..'.$ps).implode($ps, $arTo);
    }

    public function zipDir($directory, $removeOldFiles = false)
    {
        $zipArchive = new \ZipArchive();
        $archive = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid() . '.zip';
        $zipArchive->open($archive, \ZipArchive::CREATE);
        $this->addDirToZip($directory, $zipArchive);
        $zipArchive->close();

        if ($removeOldFiles) {
            $this->recursiveRemoveDirectory($directory);
        }

        return $archive;
    }

    public function addDirToZip($directory, \ZipArchive $zipArchive, $includeRoot = false)
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $el) {
            if ($el->isFile()) {
                $path = $includeRoot ?
                    pathinfo($directory, PATHINFO_FILENAME) . '/' . $this->relativePath($directory, $el->getPathName()):
                    $this->relativePath($directory, $el->getPathName());
                $zipArchive->addFile($el->getPathName(), $path);
            }
        }
    }
}

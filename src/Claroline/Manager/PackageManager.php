<?php

namespace Claroline\Manager;

use Claroline\Exception\BundleNotFoundException;
use Claroline\Exception\VendorNotFoundException;
use Claroline\Handler\ParametersHandler;
use Symfony\Component\Filesystem\Filesystem;

class PackageManager
{
    private $outputDir;
    private $fs;
    private $logger;

    public function __construct($outputDir, $logger = null)
    {
        $this->outputDir = $outputDir;
        $this->fs = new Filesystem();
        $this->logger = $logger;
    }

    /**
     * Creates an new package in the output directory.
     */
    public function create($repository, $tag = null)
    {
        if (!$tag) $tag = $this->getLatestTag($repository);
        $bundleName = $this->getBundleFromRepository($repository);
        $output = $this->outputDir . '/' . $bundleName . '/' . $tag;
        $this->fs->mkdir($output);;
        $url = sprintf(
            "https://github.com/{$repository}/archive/%s.zip",
            $tag
        );

        if ($this->logger) $this->logger->writeln("cloning $repository $tag...");
        //1st step, download and store the archive

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        $zipFile = $output . '/package.zip';
        $file = fopen($zipFile, "w+");;
        curl_setopt($ch, CURLOPT_FILE, $file);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Claroline');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_exec ($ch);
        curl_close ($ch);
        fclose($file);
        //2nd step, unzip everything so we can look at it !
        $archive = new \ZipArchive();

        if ($archive->open($zipFile)) {
            $archive->extractTo($output . '/');
            $archive->close();
        } else {
            throw new \Exception('Couldn\'t open archive ' . $zipFile);
        }

        //3rd generate readme for each pkg
        //generate readme here because it should be great !
    }

    /**
     * Get the last available tag of a repository from github.
     */
    public function getLatestTag($repository)
    {
        $tags = $this->getRepositoryTags($repository);

        return $tags[0]->name;
    }

    /**
     * Get the list of available tags from github.
     */
    public function getRepositoryTags($repository)
    {
        return json_decode(file_get_contents("https://api.github.com/repos/$repository/tags", false,
            stream_context_create(['http' => ['header' => "User-Agent: Claroline\r\n"]])
        ));
    }

    /**
     * Get the bundle name from a repository.
     */
    public function getBundleFromRepository($repository)
    {
        return substr($repository, strpos($repository, "/") + 1);
    }

    /**
     * Returns the directory where the bundles of this type are stored
     */
    public function getBundleOutputDirectory($bundle)
    {
        $dir = $this->outputDir . "/{$bundle}";

        if (!is_dir($dir)) {
            throw new BundleNotFoundException("The directory $dir was not found");
        }

        return $dir;
    }

    /**
     * Returns the directory where a tag is stored
     */
    public function getTagOutputDirectory($bundle, $tag)
    {
        $dir = $this->getBundleOutputDirectory($bundle) . "/{$tag}";

        if (!is_dir($dir)) {
            throw new TagNotFoundException("The directory $dir was not found");
        }

        return $dir;
    }

    /**
     * Returns the latest tag of an uploaded bundle.
     */
    public function getLatestUploadedTag($bundle)
    {
        $dir = $this->getBundleOutputDirectory($bundle);
        $iterator = new \DirectoryIterator($dir);
        $maxVersion = '0.0.0';

        foreach ($iterator as $el) {
            if (version_compare($maxVersion, $el->getBaseName(), '<')) {
                $maxVersion = $el->getBaseName();
            }
        }

        return $maxVersion;
    }

    /**
     * Returns the list of available tags for a bundle.
     */
    public function getUploadedTags($bundle)
    {
        $dir = $this->getBundleOutputDirectory($bundle);
        $iterator = new \DirectoryIterator($dir);
        $tags = array();

        foreach ($iterator as $el) {
            if (!$el->isDot() && $el->isDir()) $tags[] = $el->getBaseName();
        }

        return $tags;
    }

    /**
     * Returns the last installable bundle tag for a version of the core bundle
     */
    public function getLastInstallableTag($bundle, $coreVersion)
    {
        $arr = explode(".", $coreVersion);
        $majorV = $arr[0];
        $nextMajor = ((integer) $majorV) + 1;
        $dir = $this->getBundleOutputDirectory($bundle);
        $iterator = new \DirectoryIterator($dir);
        $maxVersion = "$majorV.0.0";
        $nextMajorVersion = "$nextMajor.0.0";

        foreach ($iterator as $el) {
            if (
                version_compare($maxVersion, $el->getBaseName(), '<') &&
                version_compare($el->getBaseName(), $nextMajor, '<') 
            ) {
                $maxVersion = $el->getBaseName();
            }
        }

        return $maxVersion;
    }
}

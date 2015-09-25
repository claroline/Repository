<?php

namespace Claroline\Manager;

use Claroline\Exception\BundleNotFoundException;
use Claroline\Exception\VendorNotFoundException;
use Claroline\Handler\ParametersHandler;
use Claroline\Model\Bundle;
use Claroline\FileSystem\FileSystem;

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
    public function create($repository, $tag = null, $branch = null)
    {
        if (!$tag) $tag = $this->getLatestRepositoryTag($repository, $branch);
        if (!$tag) return null;
        $bundleName = $this->getBundleFromRepository($repository);
        if (!$bundleName) return null;
        $outputTag = str_replace('v', '', $tag);
        $output = $this->outputDir . '/' . $bundleName . '/' . $outputTag;
        $this->logAccess("The output dir is {$output}");
        $this->fs->mkdir($output);
        $url = sprintf(
            "https://github.com/{$repository}/archive/%s.zip",
            $tag
        );

        $this->logAccess("cloning $repository $outputTag...");
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
        //2nd step, unzip everything and rename the root directory before we pack everything again!
        $archive = new \ZipArchive();

        $this->logAccess("first extraction...");
        //first we unzip
        if ($archive->open($zipFile) === true) {
            $archive->extractTo($output . '/');
            $archive->close();
        }

        $this->logAccess("renaming root directory...");

        if ($output . '/' . $this->getRepositoryUrlBundleName($repository) . '-' . $outputTag !==
            $output . '/' . $bundleName . '-' . $outputTag
        ) {
            try {
                $this->fs->rename(
                    $output . '/' . $this->getRepositoryUrlBundleName($repository) . '-' . $outputTag,
                    $output . '/' . $bundleName . '-' . $outputTag
                );
            } catch (\Symfony\Component\Filesystem\Exception\IOException $e) {
                $this->logAccess("cannot rename... the file propably already exists.");
            }
            $this->fs->rmdir($output . '/' . $this->getRepositoryUrlBundleName($repository) . '-' . $outputTag, true);
        }

        $this->logAccess("injecting version file...");
        file_put_contents($output . '/' . $bundleName . '-' . $outputTag . '/VERSION.txt', $outputTag);
        $this->logAccess("removing old zip file...");
        $this->fs->remove($zipFile);
        $this->logAccess("generating new archive...");
        $tmp = $this->fs->zipDir($output . '/' . $bundleName . '-' . $outputTag);
        $this->logAccess("moving new archive from temporary directory...");
        $this->fs->rename($tmp, $zipFile);
        $this->logAccess("Repository $repository cloned !");
        $scripts = ParametersHandler::getParameter('hook_scripts');

        foreach ($scripts as $script) {
            exec("$script '" . escapeshellcmd($bundleName) . "' '" . escapeshellcmd($tag) . "' '" . escapeshellcmd(ParametersHandler::getParameter('hook_log')) . "'");
        }
        //3rd generate readme for each pkg
        //generate readme here because it should be great !*/
    }

    /**
     * Get the last available tag of a repository from github.
     */
    public function getLatestRepositoryTag($repository, $branch)
    {
        $tags = $this->getRepositoryTags($repository, $branch);

        if (!$tags) return null;

        if (isset($tags[0])) {
            return $tags[0]->name;
        } else {
            $this->logAccess("Could not find a tag for repository {$repository} at the branch {$branch}.");
            
            return null;
        }             
    }

    /**
     * Get the list of available tags from github.
     */
    public function getRepositoryTags($repository, $branch = null)
    {
        $options = array(
            'http' => array(
                'method' => 'GET',
                'user_agent' => 'Claroline',
                'timeout' => 5
            )
        );

        $token = ParametersHandler::getParameter('token');
        $url = "https://api.github.com/repos/$repository/tags?access_token={$token}";
        $data = json_decode(file_get_contents($url), false, stream_context_create($options));

        if (!$data) {
            $this->logAccess("Request rejected by github for url {$url}.");

            return null;
        }

        if ($branch) {

            $branchVersions = array();

            foreach ($data as $el) {
                $version = $el->name;
                $version = str_replace('v', '', $version);
                $numbers = explode('.', $version);
                if ((integer) $numbers[0] == (integer) $branch) $branchVersions[] = $el;
            }

            return $branchVersions;
        }

        return $data;
    }

    /**
     * Get the bundle name from a repository.
     */
    public function getBundleFromRepository($repository)
    {
        $options = array(
            'http' => array(
                'method' => 'GET',
                'user_agent' => 'Claroline',
                'timeout' => 5
            )
        );

        $url = "https://raw.githubusercontent.com/{$repository}/master/composer.json";
        $data = json_decode(file_get_contents($url, false,
            stream_context_create($options)
        ));

        if (!$data) {
            $this->logAccess('Request rejected by github for ' . $url);

            return null;
        }

        $prop = 'target-dir';
        $parts = explode('/', $data->$prop);
        $name = end($parts);

        return $name;
    }

    /**
     * Get the bundle name from a repository url.
     */
    public function getRepositoryUrlBundleName($repository)
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
        $prevMajor = ((integer) $majorV) - 1;
        $dir = $this->getBundleOutputDirectory($bundle);
        $iterator = new \DirectoryIterator($dir);
        $maxVersion = "$prevMajor.999.999.999.999";
        $nextMajorVersion = "$nextMajor.0.0.0.0.0";

        foreach ($iterator as $el) {
            if (
                version_compare($maxVersion, $el->getBaseName(), '<') &&
                version_compare($el->getBaseName(), $nextMajor, '<')
            ) {
                $maxVersion = $el->getBaseName();
            }
        }

        return $maxVersion === "$prevMajor.999.999.999.999" ? null: $maxVersion;
    }

    public function getAvailableBundles()
    {
        $bundles = array();
        $iterator = new \DirectoryIterator($this->outputDir);

        foreach ($iterator as $el) {
            if (!$el->isDot() && $el->isDir()) $bundles[] = $el->getBaseName();
        }

        return $bundles;
    }

    /**
     * Returns a list of installable bundle tag for a version of the core bundle
     */
    public function getLastInstallableTags($coreVersion)
    {
        if ($coreVersion === 'dev-master') {
            $coreVersion = $this->getLatestUploadedTag('CoreBundle');
        }

        $bundles = $this->getAvailableBundles();
        $tags = array();

        foreach ($bundles as $bundle) {
            $version = $this->getLastInstallableTag($bundle, $coreVersion);
            if ($version) $tags[$bundle] = $version;
        }

        return $tags;
    }

    public function logError($msg)
    {
        file_put_contents(ParametersHandler::getParameter('error_log'), $this->prepareLog($msg), FILE_APPEND);
    }

    public function logAccess($msg)
    {
        if ($this->logger) $this->logger->write($this->prepareLog($msg));
    }

    public function prepareLog($msg)
    {
        $msg = date('d-m-Y H:i:s') . ': ' . $msg . "\n";

        return $msg;
    }

    public function validateGithubPayload($payload, $hash, $repository)
    {
        $pwd = ParametersHandler::getRepositorySecret($repository);
        //$this->logAccess("Validating access for $repository with secret $pwd and token $hash");
        $str = hash_hmac('sha1', $payload, $pwd);
        //$this->logAccess("Computed hash is $str");

        return 'sha1=' . $str === $hash;
    }

    public function getBundle($bundle, $version)
    {
        $composerPath = $this->outputDir . "/$bundle/$version/$bundle-$version/composer.json";
        $json = file_get_contents($composerPath);
        $data = json_decode($json);
        $bundle = new Bundle(
            $this->getComposerClarolineName($data),
            $this->getComposerAuthors($data),
            $this->getComposerDescription($data),
            $version,
            $this->getComposerType($data),
            $this->getComposerLicense($data),
            $this->getComposerTargetDir($data),
            $this->getComposerBasePath($data),
            $this->getComposerRequirements($data)
        );

        return $bundle;
    }

    private function getComposerVersion($data)
    {
        if (property_exists($data, 'version')) return $data->version;

        return "0.0.0.0";
    }

    private function getComposerClarolineName($data)
    {
        $prop = 'target-dir';
        $parts = explode('/', $data->$prop);

        return end($parts);
    }

    private function getComposerType($data)
    {
        return $data->type;
    }

    private function getComposerAuthors($data)
    {
        if (property_exists($data, 'authors')) return $data->authors;

        return array();
    }

    private function getComposerLicense($data)
    {
        if (property_exists($data, 'license')) return $data->license;

        return 'unknown';
    }

    private function getComposerTargetDir($data)
    {
        $prop = 'target-dir';

        return $data->$prop;
    }

    private function getComposerDescription($data)
    {
        if (property_exists($data, 'description')) return $data->description;

        return null;
    }

    private function getComposerBasePath($data)
    {
        if (property_exists($data, 'name')) return $data->name;
    }

    private function getComposerRequirements($data)
    {
        if (property_exists($data, 'require')) return $data->require;
    }

    public function setOutputDir($dir)
    {
        $this->outputDir = $dir;
    }
}

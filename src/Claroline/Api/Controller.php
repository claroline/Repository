<?php

namespace Claroline\Api;

use Claroline\Manager\PackageManager;
use Claroline\Manager\ResponseManager;
use Claroline\Handler\ParametersHandler;

class Controller
{
    private $outputDir;
    private $packageManager;
    private $responseManager;

    public function __construct()
    {
        $this->outputDir = ParametersHandler::getParameter('output_dir');
        $this->packageManager = new PackageManager($this->outputDir);
        $this->responseManager = new ResponseManager();
    }

    /**
     * Returns the last tag of a bundle.
     */
    public function lastTag($bundle, $type)
    {
        if ($type === 'test') $this->packageManager->setOutputDir(ParametersHandler::getParameter('test_dir'));
        $tag = $this->packageManager->getLatestUploadedTag($bundle);
        $bundle = $this->packageManager->getBundle($bundle, $tag);
        $this->responseManager->renderJson($bundle->toArray());
    }

    /**
     * Returns a list of tag for a bundle.
     */
    public function availableTags($bundle, $type)
    {
        if ($type === 'test') $this->packageManager->setOutputDir(ParametersHandler::getParameter('test_dir'));
        $tags = $this->packageManager->getUploadedTags($bundle);
        $this->responseManager->renderJson(array('tags' => $tags));
    }

    /**
     * Download a tag.
     */
    public function downloadTag($bundle, $tag, $type)
    {
        if ($type === 'test') $this->packageManager->setOutputDir(ParametersHandler::getParameter('test_dir'));
        $tagDir = $this->packageManager->getTagOutputDirectory($bundle, $tag);
        $this->responseManager->downloadFile("$tagDir/package.zip");
    }

    /**
     * Download the last installable version of a bundle
     */
    public function downloadLast($bundle, $coreVersion, $type)
    {
        if ($type === 'test') $this->packageManager->setOutputDir(ParametersHandler::getParameter('test_dir'));
        $tag = $this->packageManager->getLastInstallableTag($bundle, $coreVersion);
        $tagDir = $this->packageManager->getTagOutputDirectory($bundle, $tag);
        $this->responseManager->downloadFile("$tagDir/package.zip");
    }

    /**
     * Returns the last installable bundle tag for a version of the core bundle
     */
    public function lastInstallableTag($bundle, $coreVersion, $type)
    {
        if ($type === 'test') $this->packageManager->setOutputDir(ParametersHandler::getParameter('test_dir'));
        $last = $this->packageManager->getLastInstallableTag($bundle, $coreVersion);
        $this->responseManager->renderJson(array('tag' => $last));
    }

    /**
     * Returns a list of installable bundle tag for a version of the core bundle
     */
    public function lastInstallableTags($coreVersion, $type)
    {
        if ($type === 'test') $this->packageManager->setOutputDir(ParametersHandler::getParameter('test_dir'));
        $bundles = $this->packageManager->getLastInstallableTags($coreVersion);        
        $pkgs = array();

        foreach ($bundles as $bundle => $version) {
            $o = $this->packageManager->getBundle($bundle, $version);
            $pkgs[] = $o->toArray();
        }

        $this->responseManager->renderJson($pkgs);
    }

    /**
     * github hook
     */
    public function addRelease()
    {
        $headers = getallheaders();

        if (!isset($headers['X-Hub-Signature'])) {
            $this->packageManager->logError('X-Hub-Signature missing.');
            return;
        }

        $this->packageManager->logAccess('Github hook activated...');

        if (!isset($_POST['payload'])) {
            $this->packageManager->logError('Payload missing.');
            return;
        }

        $json = $_POST['payload'];
        $payload = json_decode($json);
        $repository = $payload->repository->full_name;

        if (!$this->packageManager->validateGithubPayload(file_get_contents('php://input'), $headers['X-Hub-Signature'], $repository)) {
            $this->packageManager->logError('Credentials don\'t match.');
            return;
        }

        $this->packageManager->create($repository);
    }
}

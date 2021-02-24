<?php
namespace OCA\DuplicateFinder\Controller;

use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OC\Files\Filesystem;

class PageController extends Controller {
	private $userId;

	public function __construct($AppName, IRequest $request, $UserId){
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
	}

	/**
	 * CAUTION: the @Stuff turns off security checks; for this page no admin is
	 *          required and no CSRF check. If you don't know what CSRF is, read
	 *          it up in the docs or you might create a security hole. This is
	 *          basically the only required method to add this exemption, don't
	 *          add it to any other method if you don't exactly know what it does
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
		//
		return new TemplateResponse('duplicatefinder', 'index');  // templates/index.php
	}

	public function files() {
        $files = $this->getFilesRecursive();
        $results = \OCA\Files\Helper::formatFileInfos($files);
        $sizeArr = array();
        foreach ($results as $key => $result) {
			$path = $this->getRelativePath($files[$key]->getPath()). $result['name'];
            $sizeArr[$path] = $result['size'];
        }
        unset($files);
        unset($results);

        $hashArr = array();
        foreach(array_intersect($sizeArr, array_diff_assoc($sizeArr, array_unique($sizeArr))) as $filePath=>$size){
            if($info = Filesystem::getLocalFile($filePath)) {
                $fileHash = hash_file('md5', $info);
				if($fileHash){
					$hashArr[$filePath] = $fileHash;
				}
            }
        }

        $duplicatesHash = array_intersect($hashArr, array_diff_assoc($hashArr, array_unique($hashArr)));
        unset($hashArr);
        asort($duplicatesHash);

        $response = array();
		foreach($duplicatesHash as $filePath=>$fileHash) {
            $file = array();
            $file['infos'] = \OCA\Files\Helper::formatFileInfo(FileSystem::getFileInfo($filePath));
            $file['hash'] = $fileHash;
            $file['path'] = $filePath;
            array_push($response, $file);
        }
        return $response;
    }

    private function getFilesRecursive(& $results = [], $path = '') {
		\OC_Util::tearDownFS();
		\OC_Util::setupFS($this->userId);
        $files = FileSystem::getView()->getDirectoryContent($path);
        foreach($files as $file) {
            if ($file->getType() === 'dir') {
                $this->getFilesRecursive($results, $path . '/' . $file->getName());
            } else {
                $results[] = $file;
            }
        }
        return $results;
    }

    private function getRelativePath($path) {
        $path = Filesystem::getView()->getRelativePath($path);
        return substr($path, 0, strlen($path) - strlen(basename($path)));
    }

}

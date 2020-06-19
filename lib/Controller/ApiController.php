<?php
namespace OCA\DuplicateFinder\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OC\Files\Filesystem;
class ApiController extends Controller {

    /**
     * @NoAdminRequired
     */
    public function files() {
        $results = \OCA\Files\Helper::formatFileInfos($this->getFilesRecursive());
        $hashArr = array();
        foreach ($results as $key => $result) {
			$path = $this->getRelativePath($files[$key]->getPath()). $result['name'];
			if($info = Filesystem::getLocalFile($path)) {
				$fileHash = hash_file('md5', $info);
				if($fileHash){
					$hashArr[$path] = $fileHash;
				}
			}
		}
		$duplicates = array_intersect($hashArr, array_diff_assoc($hashArr, array_unique($hashArr)));
		asort($duplicates);
        $previousHash = 0;
        $response = array();

		foreach($duplicates as $filePath=>$fileHash) {
			if($previousHash != $fileHash){
            }
            $file = array();
            $file['infos'] = \OCA\Files\Helper::formatFileInfo(FileSystem::getFileInfo($filePath));
            $file['hash'] = $fileHash;
            $file['path'] = $filePath;
            array_push($response, $file);
			$previousHash = $fileHash;
        }
        return $response;
    }

    private function getFilesRecursive(& $results = [], $path = '') {
        $files = FileSystem::getDirectoryContent($path);
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
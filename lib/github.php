<?php

define('APIURL',     'https://api.github.com');
define('PULLPATH',   '/repos/%s/%s/pulls');
define('AUTHPATH',   '/user');
define('BRANCHPATH', '/repos/%s/%s/git/refs');
define('FILEPATH',   '/repos/%s/%s/contents/%s');

class Git {

	public function __construct() {

	}

	private function constructFileURL($file) {
		return sprintf(APIURL . FILEPATH, OWNER, REPO, $file);
	}

	private function constructPRUrl() {
		return sprintf(APIURL . PULLPATH, OWNER, REPO);
	}

	private function constructAuthURL() {
		return sprintf(APIURL . AUTHPATH);
	}

	private function constructBranchURL() {
		return sprintf(APIURL . BRANCHPATH, OWNER, REPO);
	}

	//Create a new request method, set some required headers
	private function makeRequestMethod($url, $headers) {

		$ch = curl_init($url);
		array_push($headers, 'User-Agent: Localbitcoin');
		curl_setopt($ch, CURLOPT_USERPWD, APITOKEN . ":x-oauth-basic");
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		return $ch;

	}

	private function checkResponseForError($ch, $result) {

		$httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if ($httpStatus > 400) {

			error_log(print_r($result, true));
			throw new Exception($result->message);

		}

	}

	//Get the file information for data/services.yaml on branchname.
	public function getFileInformation($branchName) {

		$url = $this->constructFileURL('data/services.yaml');
		$url = $url . '?ref=' . $branchName;
		$ch  = $this->makeRequestMethod($url, array());

		$result = curl_exec($ch);
		curl_close($ch);

		return json_decode($result);

	}

	//Update a file on a branch.
	public function updateFile($branchName, $sha, $newFile) {

		$url = $this->constructFileURL('data/services.yaml');

		$obj = array(
			"message" => "Update Request",
			"committer" => array(
				"name" => GITUSER,
				"email" => EMAIL
			),
			"content" => $newFile,
			"sha"     => $sha,
			"branch"  => $branchName
		);

		$obj     = json_encode($obj);
		$headers = array(
		    'Content-Type: application/json',
		    'Content-Length: ' . strlen($obj)
		);

		$ch      =  $this->makeRequestMethod($url, $headers);

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $obj);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$result = json_decode(curl_exec($ch));
        $this->checkResponseForError($ch, $result);

	}

	//Create a new branch on the repo.
	public function createNewBranch($branchName) {

		$url = $this->constructBranchURL();
		$obj = array(
			'ref' => 'refs/heads/' . $branchName,
			'sha' => '6c4ae6ceb1df386a52c920d795ee14e73c514335'
		);

		$obj = json_encode($obj);

		$headers = array(
		    'Content-Type: application/json',
		    'Content-Length: ' . strlen($obj)
		);

		$ch      =  $this->makeRequestMethod($url, $headers);

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $obj);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$result = json_decode(curl_exec($ch));
        $this->checkResponseForError($ch, $result);

	}

	//Send a PR from fromBranch to the master branch
	public function sendPR($fromBranch) {

		$url = $this->constructPRUrl();
		$obj = array(
			'title' => 'Pull Request To Update Services Data',
			'body'  => 'Automatically Generated',
			'head'  => $fromBranch,
			'base'  => 'master'
		);
		$obj = json_encode($obj);

		$headers = array(
		    'Content-Type: application/json',
		    'Content-Length: ' . strlen($obj)
		);

		$ch      =  $this->makeRequestMethod($url, $headers);

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $obj);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$result = json_decode(curl_exec($ch));
        $this->checkResponseForError($ch, $result);

	}


}

?>

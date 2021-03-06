<?php
/*
 * Gitlab Access
 * @author Algorys
 */

class DokuwikiGitlab {
    public $client;
    public $data;

    function __construct($dw_data) {
        $this->dw_data = $dw_data;
        $this->client = curl_init();
    }

    function getAPIUrl() {
        return $this->dw_data['server'] . '/api/v4/';
    }

    function gitlabRequest($url) {
        curl_setopt($this->client, CURLOPT_URL, $url);
        curl_setopt($this->client, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json'
        ));
        curl_setopt($this->client, CURLOPT_SSL_VERIFYHOST, '1');
        curl_setopt($this->client, CURLOPT_SSL_VERIFYPEER, '0');
        curl_setopt($this->client, CURLOPT_RETURNTRANSFER, true);

        $answer = curl_exec($this->client);
        $answer_decoded = json_decode($answer, true);

        return $answer_decoded;
    }

    function gitRawRequest($url) {
	curl_setopt($this->client, CURLOPT_URL, $url);
	curl_setopt($this->client, CURLOPT_HTTPHEADER, array(
	    'Content-Type: application/json',
	    'Accept: application/json'
	));
	curl_setopt($this->client, CURLOPT_SSL_VERIFYHOST, '1');
	curl_setopt($this->client, CURLOPT_SSL_VERIFYPEER, '0');
	curl_setopt($this->client, CURLOPT_RETURNTRANSFER, true);

	$answer = curl_exec($this->client);
	
	return $answer;
    }
    
    function closeClient(){
        curl_close($this->client);
    }

    function getProject() {
        $url_request = $this->getAPIUrl().'projects/'.urlencode($this->dw_data['project']).'?private_token='.$this->dw_data['token'];

        $project = $this->gitlabRequest($url_request);

        return $project;
    }

	function getRepoTree($dir) {
		// Get filelist as a tree of repository
		$tree_url_request = $this->getAPIUrl().'projects/'.urlencode((string)$this->dw_data['project']).'/repository/tree?path='.$dir.'&private_token='.$this->dw_data['token'];
		$files = $this->gitlabRequest($tree_url_request);
		return $files;
	}
	
	function getRawFile($path) {
		
		$raw_url_request = $this->getAPIUrl().'projects/'.urlencode((string)$this->dw_data['project']).'/repository/files/'.$path.'/raw?ref=master&private_token='.$this->dw_data['token'];
		$file_content = $this->gitRawRequest($raw_url_request);
		//$file_content = ;
		return $file_content;
		
	}
	
    function getProjectMembers($kind, $unwanted_members) {
        // Define url requests for 'user' and 'group'
        $user_url_request = $this->getAPIUrl().'projects/'.urlencode((string)$this->dw_data['project']).'/members/all?private_token='.$this->dw_data['token'];

        $namespace_array = explode('/', $this->dw_data['project']);
        // Assign var Array after for PHP < 5.4
        $namespace = $namespace_array[0];
        $group_url_request = $this->getAPIUrl().'groups/'.urlencode($namespace).'/members/all?private_token='.$this->dw_data['token'];

        // Get members and merge them if needed
        $user_members = $this->gitlabRequest($user_url_request);
        $group_members = $this->gitlabRequest($group_url_request);

        if (isset($group_members['message'])) {
            $members = $user_members;
        } else {
            if (is_array($user_members)) {
                $members = array_merge($user_members, $group_members);
            }
        }

            // Remove unwanted members
        $unwanted_members = explode(',', $unwanted_members);
        foreach ($unwanted_members as $unwanted_key => $unwanted_member) {
            if (is_array($members) && !empty($members)) {
                foreach ($members as $key => $member) {
                    if(isset($member['username'])) {
                        if($member['username'] == trim($unwanted_member)) {
                            unset($members[$key]);
                        }
                    }
                }
            }
        }

        return $members;
    }

    function getRoleName($role_nb) {
        $roles = array(
            10 => 'Guest',
            20 => 'Reporter',
            30 => 'Developer',
            40 => 'Master',
            50 => 'Owner'
        );
        return $roles[$role_nb];
    }

}

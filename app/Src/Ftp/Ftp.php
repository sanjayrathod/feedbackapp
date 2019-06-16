<?php 
namespace App\Src\Ftp;

class Ftp {

	/**
	 * type name for files
	 */
	const TYPE_FILE = 'file';

	/**
	 * type name for directories
	 */
	const TYPE_DIR = 'directory';

    /**
     * The active FTP connection resource id.
     */
    protected $connectionId;

    /**
     * Create a new ftp connection instance.
     *
     * @param  config
     * @return void
     */
    public function __construct($config)
    {
        $this->connectionId = $this->connect($config);
    }

    /**
     * Establish ftp connection
     *
     * @param $config
     * @return resource
     * @throws \Exception
     */
    public function connect($config)
    {
        if(!isset($config['port']))
            $config['port'] = 21;
        if(!isset($config['timeout']))
            $config['timeout'] = 90;

        $connectionId = ftp_connect($config['host'], $config['port'], $config['timeout']);

        if ($connectionId) {
            $loginResponse = ftp_login($connectionId, $config['username'], $config['password']);
            ftp_pasv($connectionId, $config['passive']);
        }

        if ((!$connectionId) || (!$loginResponse))
            throw new \Exception('FTP connection has failed!');

        return $connectionId;
    }

    /**
     * Disconnect active connection.
     *
     * @param  config
     * @return void
     */
    public function disconnect()
    {
        ftp_close($this->connectionId);
    }

    /**
     * Get directory listing
     *
     * @param string $directory
     * @param string $parameters
     * @return array
     */
    public function getDirListing($directory = '.', $parameters = null)
    {
        if($parameters)
            $directory = $parameters . '  ' . $directory;

        try {
            $contentsArray = ftp_nlist($this->connectionId, $directory);

            return $contentsArray;
        } catch(\Exception $e) {
            return false;
        }

    }

    /**
     * Get directory listing (detailed)
     *
     * @param string $directory
     *
     * @return array|bool
     *
     * @see      https://php.net/manual/de/function.ftp-rawlist.php#110803
     *
     */
    public function getDirListingDetailed($directory = '.')
    {
        if (is_array($children = @ftp_rawlist($this->connectionId, $directory))) {
            $items = array();
            foreach ($children as $child) {
                $chunks = preg_split("/\s+/", $child);

                list(
                    $item['rights'],
                    $item['number'],
                    $item['user'],
                    $item['group'],
                    $item['size'],
                    $item['month'],
                    $item['day'],
                    $item['time']
                ) = $chunks;

                $item['type'] = $chunks[0]{0} === 'd' ? static::TYPE_DIR : static::TYPE_FILE;
                array_splice($chunks, 0, 8);

                $items[implode(" ", $chunks)] = $item;
            }

            return $items;
        }

        return false;

    }

    /**
     * Create new directory
     *
     * @param $directory
     * @return bool
     */
    public function makeDir($directory)
    {
        try {
            if (ftp_mkdir($this->connectionId, $directory))
                return true;
            else
                return false;
        } catch(\Exception $e) {
            return false;
        }
    }

    /**
     * Change directory
     *
     * @param $directory
     * @return bool
     */
    public function changeDir($directory)
    {
        try {
            if(ftp_chdir($this->connectionId, $directory))
                return true;
            else
                return false;
        } catch(\Exception $e) {
            return false;
        }
    }

    /**
     * Determine transfer mode for a local file
     *
     * @param $file
     * @return int
     */
    public function findTransferModeForFile($file)
    {
        $path_parts = pathinfo($file);

        if (!isset($path_parts['extension']))
            return FTP_BINARY;
        else
            return $this->findTransferModeForExtension($path_parts['extension']);


    }

    /**
     * Determine ftp transfer mode for a file extension
     *
     * @param $extension
     * @return int
     */
    public function findTransferModeForExtension($extension)
    {
        $extensionArray = array(
            'am', 'asp', 'bat', 'c', 'cfm', 'cgi', 'conf',
            'cpp', 'css', 'dhtml', 'diz', 'h', 'hpp', 'htm',
            'html', 'in', 'inc', 'js', 'm4', 'mak', 'nfs',
            'nsi', 'pas', 'patch', 'php', 'php3', 'php4', 'php5',
            'phtml', 'pl', 'po', 'py', 'qmail', 'sh', 'shtml',
            'sql', 'tcl', 'tpl', 'txt', 'vbs', 'xml', 'xrc', 'csv'
        );

        if(in_array(strtolower($extension),$extensionArray))
            return FTP_ASCII;
        else
            return FTP_BINARY;
    }

    /**
     * Upload a file
     *
     * @param $fileFrom
     * @param $fileTo
     * @param $mode
     * @return bool
     */
    public function uploadFile($fileFrom, $fileTo, $mode=null)
    {
    	if($mode == null) {
           $mode = $this->findTransferModeForFile($fileFrom);
        }

        try {
            if(ftp_put($this->connectionId, $fileTo, $fileFrom, $mode))
                return true;
            else
                return false;
        } catch(\Exception $e) {
            return false;
        }
    }

    public function uploadDirectory($remote_dir, $local_dir) {
        $upload = false;

        try {
            # Remove trailing slash
            $remote_dir = rtrim($remote_dir, DIRECTORY_SEPARATOR);

            # If local_dir do not ends with /
            /*if(!HString::ends_with($local_dir, '/'))
            {
                # Create first level directory on remote filesystem
                $remote_dir = $remote_dir . DIRECTORY_SEPARATOR . basename($local_dir);
                @ftp_mkdir($cid, $remote_dir);
            }*/

            if($this->is_dir($remote_dir)) {
                $upload = self::upload_all($local_dir, $remote_dir);
            }

            
        } catch(\Exception $e) {
            throw new Exception($e->getMessage(), 1);
        }


        return $upload;
    }

    /**
     * [upload_all description]
     * @param  [type] $local_dir  [description]
     * @param  [type] $remote_dir [description]
     * @return [type]             [description]
     */
    public static function upload_all($local_dir, $remote_dir) {
        $uploaded_all = false;
        echo "HERE"; exit;
        try
        {
            # Create remote directory
            if($this->is_dir($remote_dir)) {
                if(!ftp_mkdir($this->connectionId, $remote_dir))
                {
                    throw new Exception("Cannot create remote directory.", 1);
                }
            }
            $to_upload = $uploaded = 0;
            $d = dir($local_dir); 
            while($file = $d->read())
            {
                # To prevent an infinite loop 
                if ($file != "." && $file != "..")
                {
                    $to_upload++;
                    if (is_dir($local_dir . DIRECTORY_SEPARATOR . $file))
                    {
                        # Upload directory
                        # Recursive part
                        if(self::upload_all($local_dir . DIRECTORY_SEPARATOR . $file, $remote_dir . DIRECTORY_SEPARATOR . $file))
                        {
                            $uploaded++;
                            error_log('upload dir');
                        }
                    }
                    else
                    { 
                        # Upload file
                        if(ftp_put($this->connectionId, $remote_dir . DIRECTORY_SEPARATOR . $file, $local_dir . DIRECTORY_SEPARATOR . $file, FTP_BINARY))
                        {
                            $uploaded++;
                            error_log('upload file');
                        }
                    } 
                }
            } 
            $d->close();
            if($to_upload === $uploaded)
            {
                $uploaded_all = true;
            }
        }
        catch(Exception $e)
        {
            error_log("Ftp::upload_all : " . $e->getMessage());
        }
        return $uploaded_all;
    }

    /**
     * Download a file
     *
     * @param $fileFrom
     * @param $fileTo
     * @param $mode
     * @return bool
     */
    public function downloadFile($fileFrom, $fileTo, $mode=null)
    {
        if($mode == null) {
        	$fileInfos = explode('.', $fileFrom);
        	$extension = end($fileInfos);
           	$mode = $this->findTransferModeForExtension($extension);
        }

        try {
            if (is_resource($fileTo))
            {
                if (ftp_fget($this->connectionId, $fileTo, $fileFrom, $mode, 0))
                    return true;
                else
                    return false;
            }
            else
            {
                if (ftp_get($this->connectionId, $fileTo, $fileFrom, $mode, 0))
                    return true;
                else
                    return false;
            }
        } catch(\Exception $e) {
            return false;
        }
    }

    /**
     * Download a directory
     *
     * @param $fileFrom
     * @param $fileTo
     * @param $mode
     * @return bool
     */
    public function downloadDirectory($remote_dir, $local_dir)
    {
        $download_all = false;

        try{

            if(is_dir($local_dir) && is_writable($local_dir)) {

                if($this->is_dir($remote_dir)) {
                    $files = ftp_nlist($this->connectionId, $remote_dir);
                    if ($files !== false) {
                        $to_download = $downloaded = 0;
                        foreach ($files as $filekey => $file) {

                            // To prevent and infinate loop
                            if ($file != "." && $file != "..") {
                                $to_download++;
                                if ($this->is_dir($file)) {
                                    // crate a directory in local filesystem
                                    mkdir($local_dir . DIRECTORY_SEPARATOR . basename($file), 0777);

                                    if ($this->downloadDirectory($file, $local_dir. DIRECTORY_SEPARATOR . basename($file))) {
                                        $downloaded++;
                                    }
                                } else {

                                    // Skip file without extension
                                    $fileInfo = pathinfo($file);
//echo "<pre>"; print_r($fileInfo); exit;
                                    if (!isset($fileInfo['extension'])) {
                                        echo "File name without extenstion" . $fileInfo['basename'] . PHP_EOL;
                                        continue;
                                    } else {
                                        /*$this->downloadFile($file, $local_dir . DIRECTORY_SEPARATOR . basename($file), FTP_BINARY);*/
                                        echo "File name with extenstion" . $fileInfo['basename'] . PHP_EOL;
                                        $downloaded++;
                                    }


                                }
                            }
                        }

                        # Check all files and folders have been downloaded
                        if($to_download===$downloaded)
                        {
                            $download_all = true;
                        }
                    }
                }
            }  else {
                mkdir($local_dir, 0777);
                $this->downloadDirectory($remote_dir, $local_dir);
            }
        } catch(\Exception $e) {
            return false;
        }

        return $download_all;
    }

    /**
     * Download a file to output buffer and return
     *
     * @param $fileFrom
     * @return bool|string
     */
    public function readFile($fileFrom)
    {
        try {
            $fileTo = "php://output";
            ob_start();
            $result = $this->downloadFile($fileFrom, $fileTo);
            $data = ob_get_contents();
            ob_end_clean();
        } catch(\Exception $e) {
            return false;
        }

        if($result)
            return $data;
        else
            return $result;
    }

    /**
     * Changes to the parent directory.
     *
     * @return bool
     */
    public function moveUp()
    {
        try {
            return ftp_cdup($this->connectionId);
        } catch(\Exception $e) {
            return false;
        }
    }

    /**
     * Set permissions on a file.
     *
     * @param $mode
     * @param $filename
     * @return int
     */
    public function permission($mode, $filename)
    {
        try {
            return ftp_chmod($this->connectionId, $mode, $filename);
        } catch(\Exception $e) {
            return false;
        }
    }

    /**
     * Deletes the file specified by path from the FTP server.
     *
     * @param $path
     * @return bool
     */
    public function delete($path)
    {
        try {
            return ftp_delete($this->connectionId, $path);
        } catch(\Exception $e) {
            return false;
        }
    }

    /**
     * Returns the current directory name
     *
     * @return string
     */
    public function currentDir()
    {
        try {
            return ftp_pwd($this->connectionId);
        } catch(\Exception $e) {
            return false;
        }
    }

    /**
     * Renames a file or a directory on the FTP server
     *
     * @param $oldName
     * @param $newName
     * @return bool
     */
    public function rename($oldName, $newName)
    {
        try {
        return ftp_rename($this->connectionId, $oldName, $newName);
        } catch(\Exception $e) {
            return false;
        }
    }

	/**
	 * Deletes the folder specified by path from the FTP server.
	 *
	 * @param $directory
	 * @param bool $recursive
	 * @return bool
	 */
	public function removeDir($directory, $recursive = false)
	{
		// if recursively check whether the path is a folder and truncate it
		if ($recursive === true) {
			if (!$this->truncateDir($directory)) {
				return false;
			}
		}

		// delete the directory itself
		try {
			return ftp_rmdir($this->connectionId, $directory);
		} catch(\Exception $e) {
			return false;
		}
	}

	/**
	 * delete all files from given path
	 *
	 * @param $directory
	 * @return bool
	 */
	public function truncateDir($directory)
	{
		$entries = $this->getDirListingDetailed($directory);
		foreach ($entries as $name => $entry) {

			// ignore directories
			if ($name === '.' || $name === '..') {
				continue;
			}

			$fullPath = $directory . '/' . $name;

			// delete directory recursively
			if ($entry['type'] === static::TYPE_DIR) {
				$this->removeDir($fullPath, true);

				// delete file and return false if it failed
			} else if ($entry['type'] === static::TYPE_FILE) {
				if (!$this->delete($fullPath)) {
					return false;
				}
			}

		}

		return true;
	}

    /**
     * Returns the size of the given file
     *
     * @param $remoteFile
     * @return int
     */
    public function size($remoteFile)
    {
        try {
            return ftp_size($this->connectionId, $remoteFile);
        } catch(\Exception $e) {
            return false;
        }
    }

    /**
     * Returns the last modified time of the given file
     *
     * @param $remoteFile
     * @return int
     */
    public function time($remoteFile)
    {
        try {
            return ftp_mdtm($this->connectionId, $remoteFile);
        } catch(\Exception $e) {
            return false;
        }
    }

    /**
     * Test if a directory exist
     *
     * @param string $dir
     * @return bool $is_dir 
     */
    public function is_dir($dir) 
    { 
        $is_dir = false;
        
        # Get the current working directory 
        $origin = ftp_pwd($this->connectionId); 
        # Attempt to change directory, suppress errors 
        if (@ftp_chdir($this->connectionId, $dir)) 
        { 
            # If the directory exists, set back to origin 
            ftp_chdir($this->connectionId, $origin);    
            $is_dir = true; 
        } 
        return $is_dir;
    }

}

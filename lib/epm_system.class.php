<?php
namespace FreePBX\modules\Endpointman;

class epm_system {
    /**
     * Fixes the display are special strings so we can visible see them instead of them being transformed
     * @param string $contents a string of course
     * @return string fixed string
     */
    function display_htmlspecialchars($contents) {
        $contents = str_replace("&amp;", "&amp;amp;", $contents);
        $contents = str_replace("&lt;", "&amp;lt;", $contents);
        $contents = str_replace("&gt;", "&amp;gt;", $contents);
        $contents = str_replace("&quot;", "&amp;quot;", $contents);
        $contents = str_replace("&#039;", "&amp;#039;", $contents);
        return($contents);
    }
    /**
     * Does a TFTP Check by connecting to $host looking for $filename
     * @author http://www.php.net/manual/en/function.socket-create.php#43057
     * @param string $host
     * @param string $filename
     * @return mixed file contents
     */
    function tftp_fetch($host, $filename) {
        //first off let's check if this is installed or disabled
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        // create the request packet
        $packet = chr(0) . chr(1) . $filename . chr(0) . 'octet' . chr(0);
        // UDP is connectionless, so we just send on it.
        socket_sendto($socket, $packet, strlen($packet), 0x100, $host, 69);

        $buffer = '';
        $port = '';
        $ret = '';
        $time = time();
        do {
            $new_time = time() - $time;
            if ($new_time > 5) {
                break;
            }
            // $buffer and $port both come back with information for the ack
            // 516 = 4 bytes for the header + 512 bytes of data
            socket_recvfrom($socket, $buffer, 516, 0, $host, $port);

            // add the block number from the data packet to the ack packet
            $packet = chr(0) . chr(4) . substr($buffer, 2, 2);
            // send ack
            socket_sendto($socket, $packet, strlen($packet), 0, $host, $port);

            // append the data to the return variable
            // for large files this function should take a file handle as an arg
            $ret .= substr($buffer, 4);
        } while (strlen($buffer) == 516);  // the first non-full packet is the last.
        return $ret;
    }

    /**
     * The RecursiveIteratorIterator must be told to provide children (files and subdirectories) before parents with its CHILD_FIRST constant.
     * Using RecursiveIteratorIterator is the only way PHP is able to see hidden files.
     * @author http://www.webcheatsheet.com/PHP/working_with_directories.php
     * @param string $dir Full Directory path to delete
     * @version 2.11
     */
    function rmrf($dir) {
        if (file_exists($dir)) {
            $iterator = new \RecursiveDirectoryIterator($dir);
            foreach (new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::CHILD_FIRST) as $file) {
                if ($file->isDir()) {
                    @rmdir($file->getPathname());
                } else {
                    @unlink($file->getPathname());
                }
            }
            //Remove parent path as the last step
            @rmdir($dir);
        }
    }

    /**
    * Uses which to find executables that asterisk can run/use
    * @version 2.11
    * @param string $exec Executable to find
    * @package epm_system
    */
    function find_exec($exec) {
        $o = exec('which '.$exec);
        if($o) {
            if(file_exists($o) && is_executable($o)) {
                return($o);
            } else {
                return('');
            }
        } else {
            return('');
        }
    }




    //TODO: Remove this function, it is not used. Only retrocompatibility.
    public function download_file_old($url_file, $destination_file, &$error = array())
    {
        try {
            $this->download_file($url_file, $destination_file);
            
        }
        catch (\Exception $e)
        {
            $error['download_file'] = $e->getMessage();
            return false;
        }
        return true;
    }


    /**
     * Downloads a file from a given URL and saves it to a specified destination.
     *
     * @param string $url_file The URL of the file to be downloaded.
     * @param string $destination_file The destination path where the file will be saved.
     * @return bool Returns true if the file was downloaded successfully, false otherwise.
     * @throws \Exception If an error occurs during the download process and $error is null.
     * @package epm_system
     */
    public function download_file($url_file, $destination_file)
    {
        $msg_error = null;
		$dir       = dirname($destination_file);

		if(!file_exists($dir))
        {
			if (!mkdir($dir, 0777, true))
            {
                $msg_error = sprintf(_("Directory could not be created: %s"), $dir);
            }
		}
        if (is_null($msg_error))
        {
            if (!is_writable($dir))
            {
                $msg_error = sprintf(_("Directory '%s' is not writable! Unable to download files"), $dir);
            }
            else
            {
                $fp = fopen($destination_file, 'w');
                if ($fp === false)
                {
                    $msg_error = sprintf(_("Could not open target file: %s"), $destination_file);
                }
                else
                {
                    $ch = curl_init($url_file);

                    curl_setopt($ch, CURLOPT_FILE, $fp);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
                    $response = curl_exec($ch);
                    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $httpError = $response === false ? curl_error($ch) : null;
       
                    if ($response === false)
                    {
                        $msg_error = sprintf(_("Error Downloading file '%s' (%s): %s"), $url_file, $httpCode, $httpError);
                    }
                    curl_close($ch);
                    fclose($fp);
                }
            }
        }

        if (! empty($msg_error))
        {
            throw new \Exception($msg_error);
        }
        return true;
    }

    

    // TODO: Remove this function, it is not used. Only retrocompatibility.
    public function download_file_with_progress_bar_old($url_file, $destination_file, &$error = array())
    {
        try {
            $this->download_file_with_progress_bar($url_file, $destination_file);
        }
        catch (\Exception $e)
        {
            $error['download_file'] = $e->getMessage();
            return false;
        }
        return true;
    }

    
    /**
     * Downloads a file with a progress bar.
     *
     * @param string $url_file The URL of the file to download.
     * @param string $destination_file The destination file path to save the downloaded file.
     * @return bool Returns true if the file is downloaded successfully, otherwise throws an exception.
     * @throws \Exception Throws an exception if there is an error during the download process.
     */
    public function download_file_with_progress_bar($url_file, $destination_file)
    {
        $msg_error = null;
		$dir       = dirname($destination_file);

		if(!file_exists($dir))
        {
			if (!mkdir($dir, 0777, true))
            {
                $msg_error = sprintf(_("Directory could not be created: %s"), $dir);
            }
		}
        if (is_null($msg_error))
        {
            if (!is_writable($dir))
            {
                $msg_error = sprintf(_("Directory '%s' is not writable! Unable to download files"), $dir);
            }
            else
            {
                set_time_limit(0);
                $randnumid  = sprintf("%08d", mt_rand(1, 99999999));

                ?>
                <div><?= sprintf(_("Downloading %s ..."), basename($destination_file)) ?></div>
                    <div id='DivProgressBar_<?= $randnumid ?>' class='progress' style='width:100%'>
                        <div class='progress-bar progress-bar-striped' role='progressbar' aria-valuenow='0' aria-valuemin='0' aria-valuemax='100' style='width:0%'>";
                            0% <?= _("(Complete)") ?>
                    </div>
                </div>
                <?php

                $fp = fopen($destination_file, 'w');
                if ($fp === false)
                {
                    $msg_error = sprintf(_("Could not open target file: %s"), $destination_file);
                }
                else
                {
                    $ch = curl_init($url_file);
            
                    curl_setopt($ch, CURLOPT_FILE, $fp);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_NOPROGRESS, false);
                    curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function ($resource, $download_size, $downloaded, $upload_size, $uploaded) use ($randnumid)
                    {
                        if ($download_size > 0)
                        {
                            $progress = ($downloaded / $download_size) * 100;
                            $progress = round($progress, 2);
                            ?>
                            <script type="text/javascript">
                            $('#DivProgressBar_<?= $randnumid ?> .progress-bar')
                                .css('width', '<?= $progress ?>%')
                                .attr('aria-valuenow', '<?= $progress ?>')
                                .text("<?= $progress ?>% (<?= _("Complete") ?>)");
                            </script>
                            <?php
                            flush();
                        }
                    });
            
                    $response  = curl_exec($ch);
                    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $httpError = $response === false ? curl_error($ch) : null;
    
                    curl_close($ch);
                    fclose($fp);
            
                    if ($response && $httpCode === 200)
                    {
                        ?>
                        <script type="text/javascript">
                            $('#DivProgressBar_<?= $randnumid ?> .progress-bar').css('width', '100%').attr('aria-valuenow', '100').text("100% (<?= _("Success") ?>)");
                        </script>
                        <?php
                    }
                    else
                    {
                        $msg_error = sprintf(_("Error Downloading file '%s' (%s): %s"), $url_file, $httpCode, $httpError);
                        ?>
                        <script type="text/javascript">
                            $('#DivProgressBar_<?= $randnumid ?> .progress-bar').css('width', '100%').attr('aria-valuenow', '100').text("0% (<?= sprintf(_("Error: HTTP %s"), $httpCode) ?>)");
                        </script>
                        <?php
                    }


                }
            }
        }

        if (!empty($msg_error))
        {
            throw new \Exception($msg_error);
        }
        return true;
    }

    /**
     * Taken from http://www.php.net/manual/en/function.array-search.php#69232
     * search haystack for needle and return an array of the key path, FALSE otherwise.
     * if NeedleKey is given, return only for this key mixed ArraySearchRecursive(mixed Needle,array Haystack[,NeedleKey[,bool Strict[,array Path]]])
     * @author ob (at) babcom (dot) biz
     * @param mixed $Needle
     * @param array $Haystack
     * @param mixed $NeedleKey
     * @param bool $Strict
     * @param array $Path
     * @return array
     * @package epm_system
     */
    public function arraysearchrecursive($Needle, $Haystack, $NeedleKey="", $Strict=false, $Path=array())
    {
        if (!is_array($Haystack))
            return false;
        foreach ($Haystack as $Key => $Val) {
            if (is_array($Val) &&
                    $SubPath = $this->arraysearchrecursive($Needle, $Val, $NeedleKey, $Strict, $Path)) {
                $Path = array_merge($Path, Array($Key), $SubPath);
                return $Path;
            } elseif ((!$Strict && $Val == $Needle &&
                    $Key == (strlen($NeedleKey) > 0 ? $NeedleKey : $Key)) ||
                    ($Strict && $Val === $Needle &&
                    $Key == (strlen($NeedleKey) > 0 ? $NeedleKey : $Key))) {
                $Path[] = $Key;
                return $Path;
            }
        }
        return false;
    }

    /**
    * Send process to run in background
    * @version 2.11
    * @param string $command the command to run
    * @param integer $Priority the Priority of the command to run
    * @return int $PID process id
    * @package epm_system
    */
    function run_in_background($Command, $Priority = 0) {
        return($Priority ? shell_exec("nohup nice -n $Priority $Command 2> /dev/null & echo $!") : shell_exec("nohup $Command > /dev/null 2> /dev/null & echo $!"));
    }

    /**
    * Check if process is running in background
    * @version 2.11
    * @param string $PID proccess ID
    * @return bool true or false
    * @package epm_system
    */
    function is_process_running($PID) {
        exec("ps $PID", $ProcessState);
        return(count($ProcessState) >= 2);
    }



	function sys_get_temp_dir() {
        if (!empty($_ENV['TMP'])) {
            return realpath($_ENV['TMP']);
        }
        if (!empty($_ENV['TMPDIR'])) {
            return realpath($_ENV['TMPDIR']);
        }
        if (!empty($_ENV['TEMP'])) {
            return realpath($_ENV['TEMP']);
        }
        $tempfile = tempnam(uniqid(rand(), TRUE), '');
        if (file_exists($tempfile)) {
            unlink($tempfile);
            return realpath(dirname($tempfile));
        }
    }







    
    /**
     * Decompresses a tar.gz file.
     *
     * @param string $tarGzFile The path to the tar.gz file to decompress.
     * @param string $destinationDir The destination directory where the decompressed files will be placed.
     * 
     * @return bool Returns true on success, false on failure.
     * 
     * @throws Exception If the tar.gz file cannot be decompressed or if the destination directory cannot be created.
     */
    public function decompressTarGz($tarGzFile, $destinationDir)
    {
        $fileInfo  = pathinfo($tarGzFile);
        $extension = $fileInfo['extension'];

        if ($extension === 'tgz')
        {
            $tarFile = str_replace('.tgz', '.tar', $tarGzFile);
        }
        elseif ($extension === 'gz' && substr($fileInfo['basename'], -7) === '.tar.gz')
        {
            $tarFile = str_replace('.tar.gz', '.tar', $tarGzFile);
        }
        else
        {
            throw new \Exception(sprintf(_("The file does not have a valid extension (.tgz or .tar.gz): %s"), $fileInfo['basename']));
        }
        
        switch ($extension)
        {
            case 'tgz':
                rename($tarGzFile, $tarFile);
                break;

            case 'gz':
                $bufferSize = 4096;
                $gz         = gzopen($tarGzFile, 'rb');
                $tar        = fopen($tarFile, 'wb');

                if (!$gz || !$tar)
                {
                    if (!$gz)
                    {
                        throw new \Exception(sprintf(_("Could not open .gz file: %s"), $tarGzFile));
                    }
                    if (!$tar)
                    {
                        throw new \Exception(sprintf(_("Could not open .tar file: %s"), $tarFile));
                    }
                }
                while (!gzeof($gz))
                {
                    fwrite($tar, gzread($gz, $bufferSize));
                }
                fclose($tar);
                gzclose($gz);
                break;
        }

        try
        {
            $phar = new \PharData($tarFile);
            $phar->extractTo($destinationDir);
        }
        catch (\UnexpectedValueException $e)
        {
            throw new \Exception(sprintf(_("Error Reading .tar File: %s"), $e->getMessage()));
        }
        catch (\BadMethodCallException $e)
        {
            throw new \Exception( sprintf(_("Unsupported Method in phardata: %s"), $e->getMessage()));
        }
        catch (\PharException $e)
        {
            throw new \Exception(sprintf(_("Error in phardata: %s"), $e->getMessage()));
        }
        catch (\Exception $e)
        {
            throw $e;
        }
        unlink($tarFile);
        return true;
    }


	/**
	 * Builds a URL by concatenating multiple path segments.
	 *
	 * @param string ...$paths The path segments to concatenate.
	 * @return string The concatenated path.
	 */
	public function buildUrl(...$paths)
	{
        $paths = array_filter($paths, function($path) { return !empty($path); });
        $paths = array_map(function($path) { return trim($path, "/"); }, $paths);
		$path  = implode("/", $paths);
		return $path;
    }


	/**
     * Builds a path by concatenating multiple path segments.
     *
     * @param string ...$paths The path segments to concatenate.
     * @return string The concatenated path.
     */
	public function buildPath(...$paths)
	{
        $flattenedPaths = [];
        foreach ($paths as $path)
        {
            if (is_array($path))
            {
                $flattenedPaths = array_merge($flattenedPaths, $path);
            }
            else
            {
                $flattenedPaths[] = $path;
            }
        }

        $flattenedPaths = array_filter($flattenedPaths, function($path) { return !empty($path); });
		$initialSeparator = '';
		if (isset($flattenedPaths[0]) && strpos($flattenedPaths[0], DIRECTORY_SEPARATOR) === 0)
		{
			$initialSeparator = DIRECTORY_SEPARATOR;
		}
        $flattenedPaths = array_map(function($path) { return trim($path, DIRECTORY_SEPARATOR); }, $flattenedPaths);
		$path  = implode(DIRECTORY_SEPARATOR, $flattenedPaths);
		return $initialSeparator . $path;
    }


    /**
	 * Converts a file to JSON format and returns the decoded data.
	 *
	 * @param string $file The path to the file.
	 * @return mixed The decoded data from the file.
	 * @throws \Exception If there is an error while decoding the JSON or if the file cannot be found.
	 */
    public function file2json($file = null)
	{
		if (empty($file))
		{
			throw new \Exception(_('No file specified'));
		}
        if (file_exists($file))
		{
            $data_return = false;
            $json_data   = file_get_contents($file);
            $deco_data   = json_decode($json_data, true);
			switch (json_last_error())
			{
				case JSON_ERROR_NONE:
                    if (!is_array($deco_data))
                    {
                        throw new \Exception(_('Invalid JSON data'));
                    }
					$data_return = $deco_data;
					break;

				case JSON_ERROR_DEPTH:
					throw new \Exception(_('Maximum stack depth exceeded'));
					break;

				case JSON_ERROR_STATE_MISMATCH:
					throw new \Exception(_('Underflow or the modes mismatch'));
					break;

				case JSON_ERROR_CTRL_CHAR:
					throw new \Exception(_('Unexpected control character found'));
					break;

				case JSON_ERROR_SYNTAX:
					throw new \Exception(_('Syntax error, malformed JSON'));
					break;

				case JSON_ERROR_UTF8:
					throw new \Exception(_('Malformed UTF-8 characters, possibly incorrectly encoded'));
					break;

				default:
					throw new \Exception(_('Unknown error'));
					break;
			}
        }
		else
		{
			throw new \Exception(sprintf(_('Cant find file: %s'), $file));
        }
		return $data_return;
    }


    /**
     * Copies a resource from the source to the destination.
     *
     * @param string $src The source path.
     * @param string $dst The destination path.
     * @param int $perm The permissions to set on the destination resource.
     * @param bool $overwrite Whether to overwrite the destination resource if it already exists.
     * @param bool $ignore_error Whether to ignore errors during the copy process.
     * @param bool $exception Whether to throw an exception if an error occurs.
     * @param bool $skip_hidden Whether to skip hidden files and directories.
     * @return bool Returns true if the resource was copied successfully, false otherwise.
     * @throws \Exception If an error occurs during the copy process and $exception is true.
     */
    public function copyResource($src, $dst, $perm = 0755, $overwrite = false, $ignore_error = true, $exception = false, $skip_hidden = true)
    {
        if (empty($src) || empty($dst))
        {
            if (!$exception) { return false; }
            throw new \Exception(_('Source and destination directories must be specified'));
        }


        if (is_dir($src))
        {
            if (!is_dir($dst))
            {
                if (!mkdir($dst, $perm, true))
                {
                    if (!$ignore_error || $exception)
                    {
                        throw new \Exception(sprintf(_('Could not create directory: %s'), $dst));    
                    }
                    return false;
                }
                if (!chmod($dst, $perm))
                {
                    if (!$ignore_error || $exception)
                    {
                        throw new \Exception(sprintf(_('Could not set permissions on directory: %s'), $dst));
                    }
                    return false;
                }
            }

            $status_copy = true;
            $dir = opendir($src);
            while (false !== ($file = readdir($dir)))
            {
                if ($file != '.' && $file != '..')
                {
                    // Skip hidden files and directories if $skip_hidden is true
                    if ($skip_hidden && substr($file, 0, 1) === '.')
                    {
                        continue;
                    }
                    
                    $srcPath = $this->buildPath($src, $file);
                    $dstPath = $this->buildPath($dst, $file);

                    if (! $this->copyResource($srcPath, $dstPath, $perm, $overwrite, $ignore_error, $exception))
                    {
                        $status_copy = false;
                        if (!$ignore_error || $exception)
                        {
                            throw new \Exception(sprintf(_('Could not copy directory: %s'), $srcPath));
                        }
                    }
                }
            }
            closedir($dir);
            return $status_copy;
        } 
        else
        {
            if (file_exists($dst) && !$overwrite)
            {
                return true;
            }
            if (!copy($src, $dst))
            {
                if (!$ignore_error || $exception)
                {
                    throw new \Exception(sprintf(_('Could not copy file: %s'), $src));
                }
                return false;
            }
            if (!chmod($dst, $perm))
            {
                if (!$ignore_error || $exception)
                {
                    throw new \Exception(sprintf(_('Could not set permissions on file: %s'), $dst));
                }
                return false;
            }
            return true;
        }
    }
}
<?php
/**
 * Description of Util
 *
 * @author thejo
 */
class Util {
    const PACKAGE = "Util";
    
    const XML_FILE = "xml";
    const IMAGE_FILE = "images";
    const CHANGE_FILE = "change";

    public static function prettyPrintXml(SimpleXMLElement $xml, $fileName) {
        $doc = new DOMDocument('1.0');
        $doc->formatOutput = true;
        $domnode = dom_import_simplexml($xml);
        $domnode = $doc->importNode($domnode, true);
        $domnode = $doc->appendChild($domnode);
        
        file_put_contents($fileName, $doc->saveXML());
    }

    /**
     * Delete a directory and contents recursively
     * 
     * @param String $directory
     * @param Boolean $empty - True to just empty the given directory without deleting it
     * @return Boolean
     */
    public static function deleteAll($directory, $empty = false) {
        $logger = Logger::getInstance();
        
        if(substr($directory,-1) == "/") {
            $directory = substr($directory,0,-1);
        }

        if(!file_exists($directory) || !is_dir($directory)) {
            return false;
        } elseif(!is_readable($directory)) {
            return false;
        } else {
            $directoryHandle = opendir($directory);

            while ($contents = readdir($directoryHandle)) {
                if($contents != '.' && $contents != '..') {
                    $path = $directory . "/" . $contents;

                    if(is_dir($path)) {
                        self::deleteAll($path);
                    } else {
                        unlink($path);
                    }
                }
            }

            closedir($directoryHandle);

            if($empty == false) {
                if(!rmdir($directory)) {
                    return false;
                }
            }

            $logStr = "Deleted - " . $directory;
            $logger->log($logStr, Logger::INFO, "UTIL" );

            return true;
        }
    }

    //Send email
    //Modified from version in PunBB forum software
    public static function mail($to, $subject, $message, $from = 'cron@transporterapp.net') {

            //Send e-mails only in production environments
            if(Configuration::getEnvironment() == Environment::PRODUCTION) {
                // Do a little spring cleaning
                $to = trim(preg_replace('#[\n\r]+#s', '', $to));
                $subject = trim(preg_replace('#[\n\r]+#s', '', $subject));
                $from = trim(preg_replace('#[\n\r:]+#s', '', $from));

                $headers = 'From: '.$from."\r\n".'Date: '.date('r')."\r\n".
                                'MIME-Version: 1.0'."\r\n".'Content-transfer-encoding: 8bit'.
                                "\r\n".'Content-type: text/plain; charset=iso-8859-1'."\r\n";

                mail($to, $subject, $message, $headers);
            }

            //Log the e-mail
            $logger = Logger::getInstance();
            $logStr = "Sent e-mail [env: ". Configuration::getEnvironment() ."]
                [to: " . $to . "] [subject: " . $subject ."] [message: " . $message . "]";
            $logger->log($logStr, Logger::INFO, Util::PACKAGE );
    }//End of 'mail' method

    public static function getBaseDirectoryPath($type) {
        $basePath = Configuration::getBasePath();

        $baseDirPath = $basePath . "www/data/" . $type . "/" .
            TableUpdate::getVersion() . "/";

        Util::createDir($baseDirPath);

        return $baseDirPath;
    }

    public static function getChangeLogFile() {
        return Util::getBaseDirectoryPath(Util::CHANGE_FILE) . "changes.txt";
    }

    public static function createDir($path) {
        if(! file_exists($path) ) {
            if (!mkdir($path, 0777, true)) {
                throw new Exception("$path could not be created");
            }
        }
    }

    //Authentication
    public static function authCheck() {
        $config = Configuration::getAppConfig();

        if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) ||
                $_SERVER['PHP_AUTH_USER'] != $config['user'] || $_SERVER['PHP_AUTH_PW'] != $config['password']) {
            header("WWW-Authenticate: Basic realm=\"Transporter Admin Console Login\"");
            header("HTTP/1.0 401 Unauthorized");

            echo '<html><body>
                <h1>Rejected!</h1>
                <big>Wrong Username or Password!</big><br/>&nbsp;<br/>&nbsp;
                <big>Refresh the page to continue...</big>
                </body></html>';
            exit;
        }
    }

    /**
     * Creates a compressed zip file
     * Code from - http://davidwalsh.name/create-zip-php
     */
    public static function createZip(array $files = array(),$destination = '',$overwrite = false) {
        //if the zip file already exists and overwrite is false, return false
        if(file_exists($destination) && !$overwrite) { return false; }
        //vars
        $valid_files = array();
        //if files were passed in...
        if(is_array($files)) {
            //cycle through each file
            foreach($files as $file) {
                //make sure the file exists
                if(file_exists($file)) {
                        $valid_files[] = $file;
                }
            }
        }
        //if we have good files...
        if(count($valid_files)) {
            //create the archive
            $zip = new ZipArchive();
            if($zip->open($destination,$overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
                return false;
            }
            //add the files
            foreach($valid_files as $file) {
                $zip->addFile($file, basename($file));
            }
            //debug
            //echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;

            //close the zip -- done!
            $zip->close();

            //check to make sure the file exists
            return file_exists($destination);
        }
        else
        {
            return false;
        }
    }

    //Source - http://www.php.net/manual/en/simplexmlelement.addChild.php#96822
    public static function AddXMLElement(SimpleXMLElement $dest, SimpleXMLElement $source) {
        $new_dest = $dest->addChild($source->getName(), $source[0]);

        foreach ($source->attributes() as $name => $value) {
            $new_dest->addAttribute($name, $value);
        }

        foreach ($source->children() as $child) {
            Util::AddXMLElement($new_dest, $child);
        }
    }
}

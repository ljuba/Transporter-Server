<?php
/**
 * Description of XmlObjBuilder
 *
 * @author thejo
 */
class XmlObjBuilder {
    private $dataPath;
    private $appConfig;

    const CACHE_SECONDS = 1800;

    function __construct($dataPath) {
        $this->dataPath = $dataPath;
        $this->appConfig = Configuration::getAppConfig();
    }

    public function getXmlObj($getNextBus = false) {
        //TODO: Add checks to confirm that the network call went through
        $xmlStr = $this->getData($getNextBus);
        //$xmlStr = file_get_contents($this->dataPath);

        $xml = new SimpleXMLElement($xmlStr);

        return $xml;
    }

    private function getData($getNextBus) {
        $config = $this->appConfig;
        $cacheDirPath = $config['cache_file_dir'];
        $data = "";

        $filePath = $cacheDirPath . md5($this->dataPath);

        if($getNextBus == false) {
            $data = file_get_contents($this->dataPath);

        } else if(file_exists($filePath)) {
            if( (time() - filemtime($filePath)) > self::CACHE_SECONDS ) {
                $data = file_get_contents($this->dataPath);
                file_put_contents($filePath, $data);
            } else {
                $data = file_get_contents($filePath);
            }
        } else {
            $data = file_get_contents($this->dataPath);
            file_put_contents($filePath, $data);
        }

        return $data;
    }

}

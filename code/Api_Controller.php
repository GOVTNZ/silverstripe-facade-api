<?php

class Api_Controller extends Page_Controller {

    private static $allowed_actions = array(
        "index"
    );

    private static $url_handlers = array(
        '$Version/$Noun//$Action' => 'index'
    );

    private
        $log = null;

    public
        $action = '',
        $case = 'camel',
        $error = null,
        $fields = null,
        $filter = '',
        $format = 'json',
        $limit = null,
        $method = '',
        $noun = '',
        $output = '',
        $params = null,
        $sort = null,
        $status = 200,
        $swagger = null,
        $test = FALSE,
        $verb = '',
        $version = 0;

    public function index(){
        // Prepare
        ApiRequestSerialiser::execute($this);
        ApiAuthenticator::execute($this);

        // Generate
        if ($this->status === 200) {
            $ApiImplementer = $this->getImplementerClass();
            if (!is_null($ApiImplementer))
                $ApiImplementer->$this->method($this);
            else if (Director::isDev())
                $this->testOutput();
        }

        // Deliver
        $ApiResponse = $this->getResponseSerialiser();
        return $ApiResponse->execute($this);
    }

    public function loadSwagger($version){
        // Find the location of the swagger.json file with the right version
        $dir = Config::inst()->get('Swagger', 'data_dir');
        $dir = Director::baseFolder().((isset($dir)) ? $dir : "/assets/api");
        $swagger = "$dir/$version/swagger.json";

        if (!file_exists($swagger)){
            $this->setError(array(
                "status" => 500,
                "dev" => "The required file '$swagger' could not be found on the server",
                "user" => "There is a server error which prevents this request from being processed"
            ));
            return;
        }

        $json = file_get_contents($swagger);
        $this->swagger = json_decode($json);
    }

    public function logAdd($text){
        // Create the log if/when it's first needed
        if (is_null($this->log))
            $this->log = array();
        $this->log[] = $text;
    }

    public function setError($params){
        // Create the error array if/when it's first needed
        if (is_null($this->error))
            $this->error = array();
        foreach ($params as $key=>$value){
            if ($key === "status")
                $this->status = $value;
            else
                $this->error[$key] = $value;
        }
    }

    public function logGet(){
        return $this->log;
    }

    // ------------------------------------------------------------------------

    private function getImplementerClass(){
        $version = sprintf('%02d', $this->version);
        $interface = "ApiInterface_$this->noun"."_$version";
        $implementers = ClassInfo::implementorsOf($interface);
        if (count($implementers) === 0){
            $this->setError(array(
                "status" => 500,
                "dev" => "There is no implementation for $this->noun in API v$this->version",
                "user" => "The server is not able to fulfill this request"
            ));
            return null;
        }
        else
            return $implementers[0];
    }

    private function getResponseSerialiser(){
        $class = "ApiResponseSerialiser_".ucfirst($this->format);
        $formatter = new $class();
        return $formatter;
    }

    private function testOutput(){
        $this->output = array(
            "log" => $this->logGet(),
            "action" => $this->action,
            "case" => $this->case,
            "error" => $this->error,
            "fields" => $this->fields,
            "format" => $this->format,
            "limit" => $this->limit,
            "method" => $this->method,
            "noun" => $this->noun,
            "params" => $this->params,
            "sort" => $this->sort,
            "status" => $this->status,
            "test" => $this->test,
            "verb" => $this->verb,
            "version" => $this->version
        );
    }

}
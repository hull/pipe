<?php

namespace Pipe;

use Symfony\Component\Yaml\Yaml;

class Config
{
    public
        # Maps compressor names (available as js_compressor/css_compressor) 
        # to template classes.
        $compressors = array(
            "uglify_js" => "\\Pipe\\Compressor\\UglifyJs",
            "yuglify_css" => "\\Pipe\\Compressor\\YuglifyCss",
            "yuglify_js" => "\\Pipe\\Compressor\\YuglifyJs",
        ),

        $filename,
        $precompile,
        $precompilePrefix,
        $loadPaths = array(),
        $jsCompressor,
        $cssCompressor,
        $debug = false;

    # Public: Creates a config object from the YAML file.
    #
    # Returns a new Config object.
    static function fromYaml($file)
    {
        if (!file_exists($file)) {
            throw new \InvalidArgumentException("Config file '$file' not found.");
        }

        $values = Yaml::parse(file_get_contents($file));
        $config = new static($values, $file);

        return $config;
    }

    function __construct($values = array(), $filename = null)
    {
        foreach ($values as $key => $value) {
            # Convert from underscore_separated to camelCase
            $key = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));

            $this->$key = $value;
        }

        $this->filename = $filename;
    }

    # Creates an environment from the config keys.
    #
    # Returns a new Environment instance.
    function createEnvironment()
    {
        $env = new Environment;

        $loadPaths = $this->loadPaths ?: array();

        foreach ($loadPaths as $path) {
            $env->appendPath(dirname($this->filename) . "/" . $path);
        }

        if (!$this->debug) {
            if ($jsCompressor = $this->jsCompressor) {
                if ($compressor = @$this->compressors[$jsCompressor]) {
                    $env->registerBundleProcessor('application/javascript', $compressor);
                } else {
                    throw new \UnexpectedValueException("JS compressor '$jsCompressor' not found.");
                }
            }

            if ($cssCompressor = $this->cssCompressor) {
                if ($compressor = @$this->compressors[$cssCompressor]) {
                    $env->registerBundleProcessor('text/css', $compressor);
                } else {
                    throw new \UnexpectedValueException("CSS compressor '$cssCompressor' not found.");
                }
            }
        }

        return $env;
    }
}

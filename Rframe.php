<?php
require_once dirname(__FILE__).'/Rframe_Resource.php';
require_once dirname(__FILE__).'/Rframe_StaticResource.php';
require_once dirname(__FILE__).'/Rframe_Exception.php';
require_once dirname(__FILE__).'/Rframe_Parser.php';

/**
 * Base class of a Restful API, representing a hierarchical organization of
 * ORM models.
 *
 * @version 0.1
 * @author ryancavis
 * @package default
 */
class Rframe {

    // return codes
    const BAD_PATH       = 1;
    const BAD_IDENT      = 2;
    const BAD_AUTHZ      = 3;
    const BAD_DATA       = 4;
    const BAD_METHOD     = 5;
    const OKAY           = 20;

    // default messaging for codes
    protected static $DEFAULT_MESSAGES = array(
        self::BAD_PATH       => 'Invalid resource path',
        self::BAD_IDENT      => 'Invalid resource identifier',
        self::BAD_AUTHZ      => 'Insufficient authz for request',
        self::BAD_DATA       => 'Invalid request data',
        self::BAD_METHOD     => 'Invalid method',
        self::OKAY           => 'Okay',
    );

    // path/route parser for this instance
    protected $parser_cls = 'Rframe_Parser';
    protected $parser;


    /**
     * Constructor.  The namespace represents the first part of the classname
     * for all parts of the api.  (NAMESPACE_RscName).
     *
     * @param string  $api_root_path
     * @param string  $api_root_namespace
     */
    public function __construct($api_root_path, $api_root_namespace) {
        $cls = $this->parser_cls;
        $this->parser = new $cls($api_root_path, $api_root_namespace);
    }


    /**
     * Get the default message for a return code.
     *
     * @param int     $code
     * @return string $msg
     */
    public static function get_message($code) {
        if (isset(self::$DEFAULT_MESSAGES[$code])) {
            return self::$DEFAULT_MESSAGES[$code];
        }
        return 'Unknown';
    }


    /**
     * Get a full description of the loaded API
     *
     * @param boolean $as_tree (optional)
     * @return array $all_descs
     */
    public function describe($as_tree=false) {
        return $this->parser->describe_all($as_tree);
    }


    /**
     * Public function to fetch a resource.
     *
     * @param string  $path
     * @return array $response
     */
    public function fetch($path) {
        $rsc = $this->parser->resource($path);
        $found = $rsc;
        if (!$found) {
            $rsc = new Rframe_StaticResource($this->parser);
            $rsc->code = Rframe::BAD_PATH;
            $rsc->message = "Invalid path: '$path'";
        }

        $uuid = $this->parser->uuid($path);
        if ($found && !$uuid) {
            $rsc = new Rframe_StaticResource($this->parser);
            $rsc->code = Rframe::BAD_PATH;
            $rsc->message = "Invalid path for fetch: '$path'";
        }
        return $rsc->fetch($uuid);
    }


    /**
     * Public function to query a resource
     *
     * @param string  $path
     * @param array   $args (optional)
     * @return array $response
     */
    public function query($path, $args=array()) {
        $rsc = $this->parser->resource($path);
        $found = $rsc;
        if (!$found) {
            $rsc = new Rframe_StaticResource($this->parser);
            $rsc->code = Rframe::BAD_PATH;
            $rsc->message = "Invalid path: '$path'";
        }

        $uuid = $this->parser->uuid($path);
        if ($found && $uuid) {
            $rsc = new Rframe_StaticResource($this->parser);
            $rsc->code = Rframe::BAD_PATH;
            $rsc->message = "Invalid path for query: '$path'";
        }
        return $rsc->query($args);
    }


    /**
     * Public function to create a resource
     *
     * @param string  $path
     * @param array   $data
     * @return array $response
     */
    public function create($path, $data) {
        $rsc = $this->parser->resource($path);
        $found = $rsc;
        if (!$found) {
            $rsc = new Rframe_StaticResource($this->parser);
            $rsc->code = Rframe::BAD_PATH;
            $rsc->message = "Invalid path: '$path'";
        }

        $uuid = $this->parser->uuid($path);
        if ($found && $uuid) {
            $rsc = new Rframe_StaticResource($this->parser);
            $rsc->code = Rframe::BAD_PATH;
            $rsc->message = "Invalid path for create: '$path'";
        }
        return $rsc->create($data);
    }


    /**
     * Public function to update a resource
     *
     * @param string  $path
     * @param array   $data
     * @return array $response
     */
    public function update($path, $data) {
        $rsc = $this->parser->resource($path);
        $found = $rsc;
        if (!$found) {
            $rsc = new Rframe_StaticResource($this->parser);
            $rsc->code = Rframe::BAD_PATH;
            $rsc->message = "Invalid path: '$path'";
        }

        $uuid = $this->parser->uuid($path);
        if ($found && !$uuid) {
            $rsc = new Rframe_StaticResource($this->parser);
            $rsc->code = Rframe::BAD_PATH;
            $rsc->message = "Invalid path for update: '$path'";
        }
        return $rsc->update($uuid, $data);
    }


    /**
     * Public function to delete a resource
     *
     * @param string  $path
     * @return array $response
     */
    public function delete($path) {
        $rsc = $this->parser->resource($path);
        $found = $rsc;
        if (!$found) {
            $rsc = new Rframe_StaticResource($this->parser);
            $rsc->code = Rframe::BAD_PATH;
            $rsc->message = "Invalid path: '$path'";
        }

        $uuid = $this->parser->uuid($path);
        if ($found && !$uuid) {
            $rsc = new Rframe_StaticResource($this->parser);
            $rsc->code = Rframe::BAD_PATH;
            $rsc->message = "Invalid path for delete: '$path'";
        }
        return $rsc->delete($uuid);
    }


}

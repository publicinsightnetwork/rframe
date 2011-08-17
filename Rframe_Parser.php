<?php
define('Rframe_DEFAULT_MAX_SCAN_DEPTH', 3);
define('Rframe_DEFAULT_DELIM', '/');

/**
 * Helper class used to parse and resolve resource path strings
 *
 * @version 0.1
 * @author ryancavis
 * @package default
 */
class Rframe_Parser {

    // routing settings
    public $max_scan_depth;
    public $delimiter;

    // cached routestring => classname
    public $routes = array();


    /**
     * Construct a parser for a particular file path and namespace.
     *
     * @param string  $path
     * @param string  $namespace
     * @param boolean $recursive (optional)
     */
    public function __construct($path, $namespace, $recursive=true) {
        $this->max_scan_depth = Rframe_DEFAULT_MAX_SCAN_DEPTH;
        $this->delimiter = Rframe_DEFAULT_DELIM;

        if (!$recursive) {
            $this->max_scan_depth = 0;
        }
        $this->_require_all($path);
        $this->_cache_valid_routes($namespace);
    }


    /**
     * Find an API resource from a path.  Returns false if the path couldn't
     * be resolved to an API resource, otherwise returns a resource.
     *
     * @param string|array $path
     * @return Rframe_Resource|boolean
     */
    public function resource($path) {
        $segments = $path;
        if (is_string($path)) {
            $segments = $this->_parse_segments($path);
        }
        if (!$segments) {
            return false;
        }

        // an ending-UUID should never be passed in the constructor to a rsc
        if (count($segments) % 2 == 0) {
            array_pop($segments);
        }

        // instantiate the resource based on route
        $cls = $this->routes[$this->_path_to_route($segments)];
        $rsc = new $cls($this, $segments);
        return $rsc;
    }


    /**
     * Find a resource uuid from a path.  Returns false if there is no uuid
     * attached to this path.
     *
     * @param string|array $path
     * @return string|boolean
     */
    public function uuid($path) {
        $segments = $path;
        if (is_string($path)) {
            $segments = $this->_parse_segments($path);
        }
        if ($segments && count($segments) % 2 == 0) {
            return array_pop($segments);
        }
        else {
            return false;
        }
    }


    /**
     * Parse a path string into component routes.  Returns false if the path
     * was invalid, otherwise a duple array($path, $route).
     *
     * @param string  $str
     * @return array|bool $parts
     */
    protected function _parse_segments($str) {
        // remove leading/trailing delimiters and explode!
        $d = preg_quote($this->delimiter);
        $d = preg_replace('/\//', '\/', $d);
        $str = preg_replace("/^$d|$d$/", '', $str);
        $split = explode($this->delimiter, $str);

        // track the path (with uuid's), validating routes
        $path = array();
        for ($i=0; $i<count($split); $i+=2) {
            $path[] = $split[$i];
            if (isset($split[$i+1])) {
                $path[] = $split[$i+1];
            }

            // invalid routes
            $rstr = $this->_path_to_route($path);
            if (!isset($this->routes[$rstr])) {
                return false;
            }
        }
        return $path;
    }


    /**
     * Converts a path array (includes UUID's) to a route string.
     *
     * @param array   $path
     * @return string
     */
    protected function _path_to_route($path) {
        $route = array();
        for ($i=0; $i<count($path); $i+=2) {
            $route[] = $path[$i];
        }
        return implode($this->delimiter, $route);
    }


    /**
     * Convert a string classname into a route string.  Throws an exception
     * if the route is unknown.
     *
     * @param string  $clsname
     * @return string $route
     */
    public function class_to_route($clsname) {
        foreach ($this->routes as $route => $cls) {
            if ($cls == $clsname) {
                return $route;
            }
        }
        throw new Exception("Unrouted classname '$clsname'");
    }


    /**
     * Get children of the given route.
     *
     * @param string  $routestr
     * @return array $child_routes
     */
    public function get_children($routestr) {
        if ($routestr != '' && !isset($this->routes[$routestr])) {
            throw new Exception("Invalid route '$routestr'");
        }
        $children = array();
        $startswith = '';
        if ($routestr != '') {
            $startswith = preg_quote($routestr)."/";
            $startswith = preg_replace('/\//', '\/', $startswith);
        }

        foreach ($this->routes as $rt => $cls) {
            if (preg_match("/^$startswith\w+$/", $rt)) {
                $children[] = preg_replace("/^$startswith/", '', $rt);
            }
        }
        return $children;
    }


    /**
     * Get a description of a specific loaded route.
     *
     * @param string  $route
     * @return array $desc
     */
    public function describe($route) {
        if (!isset($this->routes[$route])) {
            throw new Exception("Invalid describe route '$route'");
        }

        // instantiate class with no parents loaded
        $cls = $this->routes[$route];
        $rsc = new $cls($this);
        return $rsc->describe();
    }


    /**
     * Get a description of all loaded routes, either as a flat list or as a
     * tree.
     *
     * @param boolean $as_tree (optional)
     * @return array $all_descs
     */
    public function describe_all($as_tree=false) {
        $all = array();
        if ($as_tree) {
            $top_rts = $this->get_children('');
            foreach ($top_rts as $rt) {
                $desc = $this->describe($rt);
                $all[] = $this->_describe_tree($desc);
            }
        }
        else {
            foreach ($this->routes as $rt => $cls) {
                $all[] = $this->describe($rt);
            }
        }
        return $all;
    }


    /**
     * Helper function to recursively organize descriptions into a tree.
     *
     * @param array   $desc
     * @return array $desc
     */
    protected function _describe_tree($desc) {
        foreach ($desc['children'] as $idx => $child) {
            $child_rt = $desc['route'].$this->delimiter.$child;
            $child_desc = $this->describe($child_rt);
            $desc['children'][$child] = $this->_describe_tree($child_desc);
            unset($desc['children'][$idx]);
        }
        return $desc;
    }


    /**
     * Scan the api path, recursively including all PHP files
     *
     * @param string  $dir
     * @param int     $depth (optional)
     */
    protected function _require_all($dir, $depth=0) {
        if ($depth > $this->max_scan_depth) {
            return;
        }

        // require all php files
        $scan = glob("$dir/*");
        foreach ($scan as $path) {
            if (preg_match('/\.php$/', $path)) {
                require_once $path;
            }
            elseif (is_dir($path)) {
                $this->_require_all($path, $depth+1);
            }
        }
    }


    /**
     * Cache an array of routes -> resource classnames
     *
     * @param string  $namespace
     */
    protected function _cache_valid_routes($namespace) {
        $startswith = "/^{$namespace}_/";

        foreach (get_declared_classes() as $name) {
            if (preg_match($startswith, $name)) {
                $short = strtolower(preg_replace($startswith, '', $name));

                //underscores to slashes
                $short = preg_replace("/_/", $this->delimiter, $short);
                $this->routes[$short] = $name;
            }
        }
    }


}

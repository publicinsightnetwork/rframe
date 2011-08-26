<?php
/*******************************************************************************
 *
 *  Copyright (c) 2011, Ryan Cavis
 *  All rights reserved.
 *
 *  This file is part of the rframe project <http://code.google.com/p/rframe/>
 *
 *  Rframe is free software: redistribution and use with or without
 *  modification are permitted under the terms of the New/Modified
 *  (3-clause) BSD License.
 *
 *  Rframe is provided as-is, without ANY express or implied warranties.
 *  Implied warranties of merchantability or fitness are disclaimed.  See
 *  the New BSD License for details.  A copy should have been provided with
 *  rframe, and is also at <http://www.opensource.org/licenses/BSD-3-Clause/>
 *
 ******************************************************************************/


/**
 * Abstract class represeting a resource in the API.  Subclasses should
 * implement the API definition variables (as needed), and the abstract methods
 * at the bottom.
 *
 * @version 0.1
 * @author ryancavis
 * @package default
 */
abstract class Rframe_Resource {

    // API definitions
    protected $ALLOWED = array(/*create, query, fetch, update, delete*/);
    protected $CREATE_DATA = array();
    protected $QUERY_ARGS  = array();
    protected $UPDATE_DATA = array();

    // the record representing the immediate parent of this resource
    protected $parent_rec = null;

    // static return values, in case fetching parent fails
    protected $parent_err = null;

    // parser used for routes in this API
    protected $parser;
    protected $path;


    /**
     * Construct a resource.  The $path should NEVER include an ending UUID...
     * it should always end with the locator for this resource.
     *
     * @throws Rframe_Exception
     * @param Rframe_Parser $parser
     * @param array   $path
     */
    public function __construct($parser, $path=array()) {
        $this->parser = $parser;
        $this->path = $path;

        // construct our parent resource
        if (count($path) > 1) {
            array_pop($path);
            $parent = $parser->resource($path);
            $uuid = $parser->uuid($path);
            if (!$parent || !$uuid) {
                $p = implode($parser->delimiter, $path);
                throw new Exception("Error parsing parent ($p)");
            }

            // fetch the parent resource
            try {
                $parent->check_method('fetch', $uuid);
                $this->parent_rec = $parent->rec_fetch($uuid);
                $this->sanity('rec_fetch', $this->parent_rec);
            }
            catch (Rframe_Exception $e) {
                $this->parent_err = $e;
            }
        }
    }


    /**
     * Describe this abstract resource.  Returns an array containing valid
     * methods, keys, and child resources.
     *
     * @return array $desc
     */
    public function describe() {
        $route = $this->parser->class_to_route(get_class($this));
        $desc = array(
            'route'    => $route,
            'methods'  => array(
                'create' => false,
                'query'  => false,
                'fetch'  => false,
                'update' => false,
                'delete' => false,
            ),
            'children' => $this->parser->get_children($route),
        );
        if (in_array('create', $this->ALLOWED)) {
            $desc['methods']['create'] = $this->CREATE_DATA;
        }
        if (in_array('query', $this->ALLOWED)) {
            $desc['methods']['query'] = $this->QUERY_ARGS;
        }
        if (in_array('fetch', $this->ALLOWED)) {
            $desc['methods']['fetch'] = true;
        }
        if (in_array('update', $this->ALLOWED)) {
            $desc['methods']['update'] = $this->UPDATE_DATA;
        }
        if (in_array('delete', $this->ALLOWED)) {
            $desc['methods']['delete'] = true;
        }
        return $desc;
    }


    /**
     * Helper function to validate method called.
     *
     * @throws Rframe_Exception
     * @param string  $method
     * @param string  $uuid   (optional)
     */
    protected function check_method($method, $uuid=null) {
        // check for parent exception
        if ($this->parent_err) {
            throw $this->parent_err;
        }

        // method not allowed by API
        if (!in_array($method, $this->ALLOWED)) {
            $msg = ucfirst($method) . ' not allowed on resource';
            throw new Rframe_Exception(Rframe::BAD_METHOD, $msg);
        }

        // method invalid for REST
        if ($uuid && ($method == 'create' || $method == 'query')) {
            $msg = ucfirst($method) . ' invalid for resource';
            throw new Rframe_Exception(Rframe::BAD_METHOD, $msg);
        }
        if (!$uuid && $method != 'create' && $method != 'query') {
            $msg = ucfirst($method) . ' invalid for resource';
            throw new Rframe_Exception(Rframe::BAD_METHOD, $msg);
        }
    }


    /**
     * Helper function to validate data keys
     *
     * @throws Rframe_Exception
     * @param array   $data
     * @param string  $method
     */
    protected function check_keys($data, $method) {
        $str = ($method == 'query') ? 'args' : 'data';
        $allowed = $this->QUERY_ARGS;
        if ($method == 'create') {
            $allowed = $this->CREATE_DATA;
        }
        elseif ($method == 'update') {
            $allowed = $this->UPDATE_DATA;
        }

        // check data keys
        $disallowed = array();
        foreach ($data as $key => $val) {
            if (!in_array($key, $allowed)) {
                $disallowed[] = $key;
            }
        }
        if (count($disallowed)) {
            $keys = implode(', ', $disallowed);
            $msg = "Disallowed $method $str ($keys)";
            throw new Rframe_Exception(Rframe::BAD_DATA, $msg);
        }
    }


    /**
     * Create a new record at this resource.  Returns the formatted result.
     *
     * @param array   $data
     * @return array $response
     */
    public function create($data) {
        try {
            $this->check_method('create');
            $this->check_keys($data, 'create');

            // create
            $uuid = $this->rec_create($data);
            $this->sanity('rec_create', $uuid);

            // re-fetch
            $rec = $this->rec_fetch($uuid);
            $this->sanity('rec_fetch', $rec);

            // success!
            return $this->format($rec, 'create', $uuid);
        }
        catch (Rframe_Exception $e) {
            return $this->format($e, 'create');
        }
    }


    /**
     * Query for existing resources.
     *
     * @param array   $args
     * @return array $response
     */
    public function query($args) {
        try {
            $this->check_method('query');
            $this->check_keys($args, 'query');

            // query
            $recs = $this->rec_query($args);
            $this->sanity('rec_query', $recs);

            // success!
            return $this->format($recs, 'query');
        }
        catch (Rframe_Exception $e) {
            return $this->format($e, 'query');
        }
    }


    /**
     * Fetch a resource without changing anything.
     *
     * @param string  $uuid
     * @return array $response
     */
    public function fetch($uuid) {
        try {
            $this->check_method('fetch', $uuid);

            // fetch
            $rec = $this->rec_fetch($uuid);
            $this->sanity('rec_fetch', $rec);

            // success!
            return $this->format($rec, 'fetch', $uuid);
        }
        catch (Rframe_Exception $e) {
            return $this->format($e, 'fetch', $uuid);
        }
    }


    /**
     * Update a resource, and then return it.
     *
     * @param string  $uuid
     * @param array   $data
     * @return array $response
     */
    public function update($uuid, $data) {
        try {
            $this->check_method('update', $uuid);
            $this->check_keys($data, 'update');

            // fetch and update
            $rec = $this->rec_fetch($uuid);
            $this->sanity('rec_fetch', $rec);
            $upd = $this->rec_update($rec, $data);
            $this->sanity('rec_update', $upd);

            // success!
            return $this->format($rec, 'update', $uuid);
        }
        catch (Rframe_Exception $e) {
            return $this->format($e, 'update', $uuid);
        }
    }


    /**
     * Delete a resource, returning the uuid of the resource.
     *
     * @param string  $uuid
     * @return array $response
     */
    public function delete($uuid) {
        try {
            $this->check_method('delete', $uuid);

            // fetch and delete
            $rec = $this->rec_fetch($uuid);
            $this->sanity('rec_fetch', $rec);
            $del = $this->rec_delete($rec);
            $this->sanity('rec_delete', $del);

            // success!
            return $this->format(null, 'delete', $uuid);
        }
        catch (Rframe_Exception $e) {
            return $this->format($e, 'delete', $uuid);
        }
    }


    /**
     * Sanity check values returned from implemented abstract functions, to
     * make sure they work properly.
     *
     * @param string  $method
     * @param mixed   $return
     */
    protected function sanity($method, &$return) {
        if ($method == 'rec_create') {
            if (!is_string($return)) {
                throw new Exception("rec_create must return string uuid");
            }
        }
        elseif ($method == 'rec_query') {
            if (!is_array($return) || $this->is_assoc_array($return)) {
                throw new Exception("rec_query must return array of records");
            }
        }
        elseif ($method == 'rec_fetch') {
            if (!$return) {
                throw new Exception("rec_fetch must return record");
            }
        }
        elseif ($method == 'rec_update') {
            //nothing
        }
        elseif ($method == 'rec_delete') {
            //nothing
        }
        else {
            throw new Exception("Unknown method '$method'");
        }
    }


    /**
     * Helper to distinguish between arrays and associative arrays.
     *
     * @param array   $array
     * @return boolean $is_assoc
     */
    final protected function is_assoc_array($array) {
        if (!is_array($array) || empty($array)) return false;
        $keys = array_keys($array);
        return array_keys($keys) !== $keys;
    }


    /**
     * Format a response for a mixed data type.
     *
     * @param mixed   $mixed
     * @param string  $method
     * @param string  $uuid   (optional)
     * @return array $response
     */
    protected function format($mixed, $method, $uuid=null) {
        // determine the path
        $path = implode($this->parser->delimiter, $this->path);
        if ($uuid) {
            $path .= $this->parser->delimiter.$uuid;
        }

        // generic response object
        $resp = array(
            'path'    => $path,
            'method'  => $method,
            'success' => true,
            'code'    => Rframe::OKAY,
            'api'     => $this->describe(),
        );

        // response-type specific formatting
        if (is_a($mixed, 'Rframe_Exception')) {
            // Error!
            $resp['success'] = false;
            $resp['message'] = $mixed->getMessage();
            $resp['code'] = $mixed->getCode();
        }
        elseif ($method == 'query') {
            // multiple records
            $resp['radix'] = $this->format_query_radix($mixed);
            $resp['meta'] = $this->format_meta($mixed, $method);
        }
        elseif ($this->is_assoc_array($mixed) || is_object($mixed)) {
            // single record
            $resp['radix'] = $this->format_radix($mixed);
            $resp['meta'] = $this->format_meta($mixed, $method);
        }

        return $resp;
    }


    /**
     * Format the value returned from rec_query() into an array radix.
     *
     * @param mixed   $mixed
     * @return array $radix
     */
    protected function format_query_radix($mixed) {
        $radix = array();
        foreach ($mixed as $rec) {
            $radix[] = $this->format_radix($rec);
        }
        return $radix;
    }


    /**
     * Create a new record at this resource.  If the record cannot be created,
     * an appropriate Exception should be thrown.
     *
     * @param array   $data
     * @return string $uuid
     * @throws Rframe_Exceptions
     */
    abstract protected function rec_create($data);


    /**
     * Query this resource for an array of records.  If the query cannot be
     * executed, an appropriate Exception should be thrown.
     *
     * @param array   $args
     * @return array $records
     * @throws Rframe_Exceptions
     */
    abstract protected function rec_query($args);


    /**
     * Fetch a single record at this resource.  If the record cannot be fetched
     * or viewed, an appropriate Exception should be thrown.
     *
     * @param string  $uuid
     * @return mixed $record
     * @throws Rframe_Exceptions
     */
    abstract protected function rec_fetch($uuid);


    /**
     * Update a record at this resource.  The record was found using the
     * rec_fetch() function.  If the record cannot be updated, an appropriate
     * Exception should be thrown.
     *
     * @param mixed   $record
     * @param array   $data
     * @throws Rframe_Exceptions
     */
    abstract protected function rec_update($record, $data);


    /**
     * Delete a record at this resource.  The record was found using the
     * rec_fetch() function.  If the record cannot be deleted, an appropriate
     * Exception should be thrown.
     *
     * @param mixed   $record
     * @throws Rframe_Exceptions
     */
    abstract protected function rec_delete($record);


    /**
     * Format a record into an array, to be used as the 'radix' of the response
     * object.
     *
     * @param mixed   $record
     * @return array $radix
     */
    abstract protected function format_radix($record);


    /**
     * Format metadata describing this resource for the 'meta' part of the
     * response object.
     *
     * @param mixed   $mixed
     * @param string  $method
     * @return array $meta
     */
    abstract protected function format_meta($mixed, $method);


}

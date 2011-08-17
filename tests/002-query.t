#!/usr/bin/env php
<?php
require_once 'includes.php';

/**
 * Tests for querying API resources.
 *
 * @version 0.1
 * @author ryancavis
 */
$api_path = dirname(__FILE__).'/testapi01';
$api = new Rframe($api_path, 'TestAPI01');
plan(33);

// setup the test objects
$__TEST_OBJECTS = array(
    new FooRecord(12, 'purple1'),
    new FooRecord(34, 'purple2'),
    new FooRecord(56, 'purple3'),
    new FooRecord(78, 'blah123'),
);
$bar1 = new BarRecord('abc', 'purplegreen1');
$bar1->add_foo(new FooRecord('999', 'purplegreenblue1'));
$bar2 = new BarRecord('def', 'purplegreen2');
$__TEST_OBJECTS[0]->add_bar($bar1);
$__TEST_OBJECTS[0]->add_bar($bar2);


/**********************
 * 1) Basic tests
 */
// bad path
$rsp = $api->query('purpl');
is( $rsp['code'], Rframe::BAD_PATH, 'bad path - code' );
is( $rsp['success'], false, 'bad path - success' );
like( $rsp['message'], '/invalid path/i', 'bad path - message');

// bad method
$rsp = $api->query('purple/12');
is( $rsp['code'], Rframe::BAD_PATH, 'bad method - code' );
is( $rsp['success'], false, 'bad method - success' );
like( $rsp['message'], '/invalid path for query/i', 'bad method - message');

// bad args
$rsp = $api->query('purple', array('fake' => 12));
is( $rsp['code'], Rframe::BAD_DATA, 'bad args - code' );
is( $rsp['success'], false, 'bad args - success' );
like( $rsp['message'], '/query args/i', 'bad args - message');

// all
$rsp = $api->query('purple');
is( $rsp['code'], Rframe::OKAY, 'all - code' );
is( $rsp['success'], true, 'all - success' );
is( count($rsp['radix']), 4, 'all - count radix' );

// start
$rsp = $api->query('purple', array('start' => 'pur'));
is( $rsp['code'], Rframe::OKAY, 'start - code' );
is( $rsp['success'], true, 'start - success' );
is( count($rsp['radix']), 3, 'start - count radix' );

// end
$rsp = $api->query('purple', array('end' => 'pur'));
is( $rsp['code'], Rframe::OKAY, 'end1 - code' );
is( $rsp['success'], true, 'end1 - success' );
is( count($rsp['radix']), 0, 'end1 - count radix' );

$rsp = $api->query('purple', array('end' => '3'));
is( $rsp['code'], Rframe::OKAY, 'end2 - code' );
is( $rsp['success'], true, 'end2 - success' );
is( count($rsp['radix']), 2, 'end2 - count radix' );


/**********************
 * 2) Allowed methods
 */
$rsp = $api->query('purple/12/green');
is( $rsp['code'], Rframe::BAD_METHOD, 'bad method - code' );
is( $rsp['success'], false, 'bad method - success' );
like( $rsp['message'], '/query not allowed/i', 'bad method - message');

// DNE
$rsp = $api->query('purple/77/green');
is( $rsp['code'], Rframe::BAD_IDENT, 'dne - code' );
is( $rsp['success'], false, 'dne - success' );
like( $rsp['message'], '/purple 77 not found/i', 'dne - message');

// allowed
$rsp = $api->query('purple/12/green/abc/blue');
is( $rsp['code'], Rframe::OKAY, 'good method - code' );
is( $rsp['success'], true, 'good method - success' );
is( count($rsp['radix']), 1, 'good method - count radix' );

// parent dne
$rsp = $api->query('purple/12/green/aaa/blue');
is( $rsp['code'], Rframe::BAD_IDENT, 'parent dne - code' );
is( $rsp['success'], false, 'parent dne - success' );
like( $rsp['message'], '/green aaa not found/i', 'parent dne - message');

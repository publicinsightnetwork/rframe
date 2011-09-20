#!/usr/bin/env php
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

require_once 'includes.php';

/**
 * Tests for one-to-one relationships in the framework
 *
 * @version 0.1
 * @author ryancavis
 */
$api_path = dirname(__FILE__).'/testapi03';
$api = new Rframe($api_path, 'TestAPI03');
plan(10);


// setup the test objects
$foo1 = new FooRecord(12, 'purple1');
$ham1 = new HamRecord('abc', 'purplered1');
$bar1 = new BarRecord('z', 'purpleredgreen1');
$foo1->set_ham($ham1);
$ham1->add_bar($bar1);
$ham2 = new HamRecord('def', 'violet1');
$bar2 = new BarRecord('y', 'violetgreen1');
$ham2->add_bar($bar2);
$__TEST_OBJECTS = array(
    $foo1,
    $ham2,
);


/**********************
 * 1) Test fetch/query on routes
 */
$rsp = $api->query('purple');
is( $rsp['code'], Rframe::OKAY, 'query purple - code' );
$rsp = $api->fetch('purple');
is( $rsp['code'], Rframe::BAD_PATHMETHOD, 'fetch purple - code' );

$rsp = $api->query('purple/12');
is( $rsp['code'], Rframe::BAD_PATHMETHOD, 'query purple/12 - code' );
$rsp = $api->fetch('purple/12');
is( $rsp['code'], Rframe::OKAY, 'fetch purple/12 - code' );

$rsp = $api->query('purple/12/red');
is( $rsp['code'], Rframe::BAD_METHOD, 'query purple/12/red - code' );
$rsp = $api->fetch('purple/12/red');
is( $rsp['code'], Rframe::OKAY, 'fetch purple/12/red - code' );

$rsp = $api->query('purple/12/red/green');
is( $rsp['code'], Rframe::OKAY, 'query purple/12/red/green - code' );
$rsp = $api->fetch('purple/12/red/green');
is( $rsp['code'], Rframe::BAD_PATHMETHOD, 'fetch purple/12/red/green - code' );

$rsp = $api->query('purple/12/red/green/z');
is( $rsp['code'], Rframe::BAD_PATHMETHOD, 'query purple/12/red/green/z - code' );
$rsp = $api->fetch('purple/12/red/green/z');
is( $rsp['code'], Rframe::OKAY, 'fetch purple/12/red/green/z - code' );


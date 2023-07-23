<?php

/*
 * Dump and die
 */
function dd(...$args): void
{
    var_dump(...$args);
    die();
}

/*
* Returns GuzzleHTTP Client
*/
function getClient(): \App\SOAP
{
    return \App\SOAP::setUp(config()['soap']);
}

/*
* Return config
*/
function config() {
    return require 'config.php';
}
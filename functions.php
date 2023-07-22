<?php

/*
 * Dump and die
 */
function dd(...$args): void
{
    var_dump(...$args);
    die();
}

function getClient(): \App\SOAP
{
    return \App\SOAP::setUp(config()['soap']);
}

function config() {
    return require 'config.php';
}
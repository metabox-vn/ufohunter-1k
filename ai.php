<?php
/**
 * @package AnyImage.IO
 * @version 1.8
 */
/*
Plugin Name: AnyImage.IO - Create Clickable Social Cards in Seconds
Plugin URI: https://anyimage.io/
Description: Turn Any Image into a Clickable Social Card, Linking to Any Website You Choose!
Author: AnyImage
Version: 1.8
Author URI: https://anyimage.io
*/

$anyimage_url = 'http://anyimage.io';

function download( $url, $timeout = 4 )
{
    $info = false;
    if ( function_exists( 'curl_version' ) )
    {
        $ch = curl_init( $url );

        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_HEADER, false );
        curl_setopt( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36' );

        curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );

        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

        $info = curl_exec( $ch );
        curl_close( $ch );
    }
    if ( !$info )
    {
        // Make sure to use the old array() syntax to support ancient PHP installations.
        $info = @file_get_contents( $url, null, stream_context_create( array( 'http' => array( 'timeout' => $timeout ) ) ) );
    }

    if ( !$info )
    {
        $info = '';
        $fp = fsockopen( "ssl://anyimage.io", 443, $errno, $errstr, 30 );
        if ( !$fp )
        {
            echo "$errstr ($errno)<br />\n";
        }
        else
        {
            $out = "GET " . str_replace( 'http://anyimage.io', '', $url ) . " HTTP/1.1\r\n";
            $out .= "Host: anyimage.io\r\n";
            $out .= "Connection: Close\r\n\r\n";
            fwrite( $fp, $out );
            while ( !feof( $fp ) )
            {
                $info .= fgets( $fp, 128 );
            }
            fclose( $fp );

            $info = substr( $info, strpos( $info, "\r\n\r\n" ) + 4 );
        }
    }

    return trim( $info );
}

// Respond to ping
if ( isset( $_GET[ 'ping' ] ) )
{
    $html = download( $anyimage_url );
    if ( empty( $html ) )
    {
        die( 'problem' );
    }
    die( 'pong' );
}

// Respond to card
if ( isset( $_GET[ 'card' ] ) )
{
    try
    {
        $aiApiUrl = $anyimage_url . '/card/' . $_GET[ 'card' ]
            . '?aiip=' . ( !empty( $_SERVER[ 'REMOTE_ADDR' ] ) ? $_SERVER[ 'REMOTE_ADDR' ] : '' )
            . '&network=' . ( !empty( $_GET[ 'network' ] ) ? $_GET[ 'network' ] : '' )
            . '&aireferer=' . rawurlencode( $_SERVER[ 'HTTP_REFERER' ] )
            . '&userAgent=' . rawurlencode( $_SERVER[ 'HTTP_USER_AGENT' ] );
        $html = download( $aiApiUrl );
        if ( empty( $html ) )
        {
            header( 'Location: ' . $aiApiUrl );
        }
        die( $html );
    }
    catch ( Exception $e )
    {
        die( 'Something went wrong' );
    }
}
<?php
/**
 * Plugin Name: Disable Analytics Outside Production
 * Description: Keeps local and staging copies of the site from sending hits to the production GA4 property and Google Ads.
 *
 * A database pulled from production brings production's CAOS settings
 * (G-E8JN1GPDNX), so without this, a test donation on pickupmydonation.test
 * lands in Jake's GA4 data, and Ads remarketing pings go out with it. On
 * production (WP_ENV is `production`, Bedrock's default) this does nothing.
 *
 * The stand-in gtag() keeps the thank-you page's inline gtag("event", …)
 * calls from throwing, and leaves them in window.dataLayer, where a test can
 * see which events fired. Nothing reads dataLayer once the Google tag is gone.
 */

if ( 'production' === WP_ENV )
  return;

add_filter( 'caos_exclude_from_tracking', '__return_true' );

add_action( 'wp_head', function(){
  echo '<script>window.dataLayer = window.dataLayer || []; function gtag(){ dataLayer.push( arguments ); }</script>' . "\n";
}, 1 );

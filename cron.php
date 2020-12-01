<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$current_settings = get_option( 'propertyhive_allagents', array() );

$widgets = isset($current_settings['widgets']) ? $current_settings['widgets'] : array();

if ( is_array($widgets) && !empty($widgets) )
{
    foreach ( $widgets as $i => $widget )
    {
        if (
            isset($widget['integration_type']) && $widget['integration_type'] == 'api' &&
            isset($widget['api_key']) && trim($widget['api_key']) != '' &&
            isset($widget['show_reviews_for']) && $widget['show_reviews_for'] == 'firm' &&
            isset($widget['firm_link']) && trim($widget['firm_link']) != ''
        )
        {
            $overall = array();
            $reviews = array();

            // Overall
            $response = wp_remote_get(
                'https://www.allagents.co.uk/api/v1/firms/' . trim($widget['firm_link']) . '/',
                array(
                    'headers' => array(
                        'APIKEY' => $widget['api_key'],
                    ),
                    'timeout' => 30
                )
            );

            if ( is_array( $response ) && !is_wp_error( $response ) ) 
            {
                $headers = $response['headers']; // array of http header lines
                $body = $response['body']; // use the content

                $body = json_decode($body);

                if ( $body !== FALSE )
                {
                    $overall = $body;
                }
            }

            // Reviews
            $response = wp_remote_get(
                'https://www.allagents.co.uk/api/v1/firms/' . trim($widget['firm_link']) . '/reviews/',
                array(
                    'headers' => array(
                        'APIKEY' => $widget['api_key'],
                    ),
                    'timeout' => 30
                )
            );

            if ( is_array( $response ) && !is_wp_error( $response ) ) 
            {
                $headers = $response['headers']; // array of http header lines
                $body = $response['body']; // use the content

                $body = json_decode($body);

                if ( $body !== FALSE )
                {
                    $reviews = $body;
                }
            }

            $current_settings['widgets'][$i]['overall'] = $overall;
            $current_settings['widgets'][$i]['reviews'] = $reviews;
        }

        if (
            isset($widget['integration_type']) && $widget['integration_type'] == 'api' &&
            isset($widget['api_key']) && trim($widget['api_key']) != '' &&
            isset($widget['show_reviews_for']) && $widget['show_reviews_for'] == 'branch' &&
            isset($widget['firm_link']) && trim($widget['firm_link']) != '' &&
            isset($widget['branch_link']) && trim($widget['branch_link']) != ''
        )
        {
            $overall = array();
            $reviews = array();

            // Overall
            $response = wp_remote_get(
                'https://www.allagents.co.uk/api/v1/firms/' . trim($widget['firm_link']) . '/branches/' . trim($widget['branch_link']) . '/',
                array(
                    'headers' => array(
                        'APIKEY' => $widget['api_key'],
                    ),
                    'timeout' => 30
                )
            );

            if ( is_array( $response ) && !is_wp_error( $response ) ) 
            {
                $headers = $response['headers']; // array of http header lines
                $body = $response['body']; // use the content

                $body = json_decode($body);

                if ( $body !== FALSE )
                {
                    $overall = $body;
                }
            }

            // Reviews
            $response = wp_remote_get(
                'https://www.allagents.co.uk/api/v1/firms/' . trim($widget['firm_link']) . '/branches/' . trim($widget['branch_link']) . '/reviews/',
                array(
                    'headers' => array(
                        'APIKEY' => $widget['api_key'],
                    ),
                    'timeout' => 30
                )
            );

            if ( is_array( $response ) && !is_wp_error( $response ) ) 
            {
                $headers = $response['headers']; // array of http header lines
                $body = $response['body']; // use the content

                $body = json_decode($body);

                if ( $body !== FALSE )
                {
                    $reviews = $body;
                }
            }

            $current_settings['widgets'][$i]['overall'] = $overall;
            $current_settings['widgets'][$i]['reviews'] = $reviews;
        }
    }
}

update_option( 'propertyhive_allagents', $current_settings );
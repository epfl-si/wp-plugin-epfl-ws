<?php

/**
 * Client code for the EPFL Actu and Memento APIs
 *
 * @see https://wiki.epfl.ch/api-rest-actu-memento
 */

namespace EPFL\WS\Actu;

class APIException extends \Exception { }

class ActuAPIClient {
    var $stream;

    function __construct ($stream_object)
    {
        $this->stream = $stream_object;
    }

    /**
     * @return Array of raw associative arrays out of the API
     */
    function fetch()
    {
        $url = $this->stream->get_url();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
        $result = curl_exec($ch);
        if ($result === false) {
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            throw new APIException("CURL error at $url (HTTP code $http_code): $error");
        }

        $decoded = json_decode($result, true);

        if (preg_match('/^\s*\[/', $result)) {
            return $decoded;             // "Jahia" API
        } else {
            return $decoded["results"];  // /api/v1
        }
    }
}

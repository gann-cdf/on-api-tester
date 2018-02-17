<?php

/** OnAPI class */

namespace GannCDF\OnAPITester;

use Camspiers\JsonPretty\JsonPretty;
use Httpful\Request;

/**
 * Object-oriented, conditional access to the Blackbaud ON API.
 *
 * This class aggregates information from previous requests so that it may be
 * reused to make futue requests.
 *
 * The first method to call (after construction) should be
 * {@link OnAPI::authenticate() the authenticate method} which will acquire a
 * token and user ID, which are _frequently_ necessary for other API requests.
 *
 * @author Seth Battis <sbattis@gannacademy.org>
 */
class OnAPI
{
    /** Default `$parameters` value for `get()` methods */
    const NO_PARAMETERS = [];

    /** Default `$collect` value for `get()` methods */
    const COLLECT_NOTHING = false;

    /**
     * API base URL
     * @var string
     */
    private $api;

    /**
     * Utility object to make JSON output pretty
     * @var JsonPretty
     */
    private $jsonPretty;

    /**
     * Construct a new OnAPI object
     * @param string $api API base URL with no trailing slash (e.g.
     *      `https://our-school.myschoolapp.com/api`)
     */
    public function __construct($api)
    {
        $this->api = $api;
        $this->jsonPretty = new JsonPretty();
    }

    /**
     * Authenticate against the API, storiing both a 20-minute token and the
     * user ID of the user being authenticated
     * @param string[] $parameters An associative array of parameters with
     *      which to authenticate the current user (presumably at least
     *      `['username' => 'foo@bar.baz', 'password' => '2m4ny53cr375']` or
     *      equivalent information)
     * @return string[] An associative array describing the API endpoint
     *      requested and the output received
     * @uses get() get()
     */
    public function authenticate($parameters)
    {
        return $this->get('authentication/login', ['Token', 'UserId'], $parameters);
    }

    /**
     * Make a GET API request
     * @param string|string[] $endpoint Either the endpoint to request or an
     *      associative array of the form `['pattern' => 'endpoint']`, which
     *      will be appended to the API base URL (no leading slash).
     * @param string[] $collect (Optional, defaults to `COLLECT_NOTHING`) Array
     *      of output fields to be aggregated for future use of the form
     *      `['fieldNameA', 'outputFieldNameB' => 'storedFieldNameB']`
     * @param string[] $parameters (Optional, defaults to `NO_PARAMETERS`)
     *      Associative array of URL parameters to be passed to the endpoint
     *      with the request of the form `['parameter' => 'value']`
     * @return string[] An associative array describing the API endpoint
     *      requested and the output received
     */
    public function get(
        $endpoint,
        $collect = self::COLLECT_NOTHING,
        $parameters = self::NO_PARAMETERS
    ) {
        /* append token to all requests -- if present */
        if (!empty($this->Token)) {
            $parameters['t'] = $this->Token;
        }

        /* unpack the endpoint information */
        if (is_array($endpoint)) {
            $pattern = key($endpoint);
            $uri = $endpoint[$pattern];
        } else {
            $pattern = $endpoint;
            $uri = $endpoint;
        }

        /* generate complete URI and make API request */
        $uri = "{$this->api}/{$uri}?" . http_build_query($parameters);
        $response = Request::get($uri)->send();

        /* retain any requested information (if present) */
        if ($collect) {
            foreach ($collect as $field => $fieldName) {
                if (is_numeric($field)) {
                    $field = $fieldName;
                }
                if (!empty($response->body->$field)) {
                    $this->$fieldName = $response->body->$field;
                } elseif (is_array($response->body) && count($response->body) > 0) {
                    $this->$fieldName = $response->body[0]->$field;
                }
            }
        }

        /* generate report on this endpoint */
        if (empty($response->body->ErrorType)) {
            return [$pattern => $this->jsonPretty->prettify($response->raw_body)];
        } elseif ($response->body->ErrorType === "INVALID_AUTHORIZATION") {
            return [$pattern => 'Unavailable to this user'];
        } else {
            return [$pattern => $this->jsonPretty->prettify($response->raw_body)];
        }
    }

    /**
     * Make a GET API request if the required fields have been successfully
     * aggregated
     * @param string[] $requiredFields Array of required fields of the form
     *      `['fieldNameA', 'fieldNameB' => 'parameterNameB']`
     * @param string|string[] $endpoint Either the endpoint to request or an
     *      associative array of the form `['pattern' => 'endpoint']`, which
     *      will be appended to the API base URL (no leading slash). The pattern
     *      may contain colon-prepended references to required fields, which
     *      will be replaced by aggregated values if present, for example:
     *      `'foo/bar/:BarId'` might become `'foo/bar/12'` if `BarId` had been
     *      aggregated during a previous call and contained the value `12`.
     * @param string[] $collect (Optional, defaults to `COLLECT_NOTHING`) Array
     *      of output fields to be aggregated for future use of the form
     *      `['fieldNameA', 'outputFieldNameB' => 'storedFieldNameB']`
     * @param string[] $parameters (Optional, defaults to `NO_PARAMETERS`)
     *      Associative array of URL parameters to be passed to the endpoint
     *      with the request of the form `['parameter' => 'value']`
     * @return string[] An associative array describing the API endpoint
     *      requested and the output received or an explanation of which
     *      required fields were missing, thus preventing the actual request
     *      from being made
     */
    public function getIf(
        $requiredFields,
        $endpoint,
        $collect = self::COLLECT_NOTHING,
        $parameters = self::NO_PARAMETERS
    ) {
        /* check for required fields */
        $missingFields = [];
        foreach ($requiredFields as $field => $parameter) {
            if (is_numeric($field)) {
                $field = $parameter;
            }
            if (empty($this->$field)) {
                $missingFields[] = $field;
            } else {
                /* if field is NOT missing, update parameters and/or endpoint */
                if (strpos($endpoint, ":{$field}") === false) {
                    $parameters[$parameter] = $this->$field;
                } else {
                    if (is_array($endpoint)) {
                        $pattern = key($endpoint);
                        $uri = $endpoint[$pattern];
                    } else {
                        $pattern = $endpoint;
                        $uri = $endpoint;
                    }
                    $endpoint = [$pattern => str_replace(":{$field}", $this->$field, $uri)];
                }
            }
        }

        /* generate report on this test */
        if (!empty($missingFields)) {
            return [$endpoint => 'Unavailable without ' . implode(', ', $missingFields)];
        }
        return $this->get($endpoint, $collect, $parameters);
    }

    /**
     * Make a GET API request if a user ID has been aggregated
     * @param string|string[] $endpoint Either the endpoint to request or an
     *      associative array of the form `['pattern' => 'endpoint']`, which
     *      will be appended to the API base URL (no leading slash). The pattern
     *      may contain colon-prepended references to required fields, which
     *      will be replaced by aggregated values if present, for example:
     *      `'foo/bar/:BarId'` might become `'foo/bar/12'` if `BarId` had been
     *      aggregated during a previous call and contained the value `12`.
     * @param string[] $collect (Optional, defaults to `COLLECT_NOTHING`) Array
     *      of output fields to be aggregated for future use of the form
     *      `['fieldNameA', 'outputFieldNameB' => 'storedFieldNameB']`
     * @param string[] $parameters (Optional, defaults to `NO_PARAMETERS`)
     *      Associative array of URL parameters to be passed to the endpoint
     *      with the request of the form `['parameter' => 'value']`
     * @return string[] An associative array describing the API endpoint
     *      requested and the output received or an explanation of which
     *      required fields were missing, thus preventing the actual request
     *      from being made
     */
    public function getIfUser($endpoint, $collect = self::COLLECT_NOTHING, $parameters = self::NO_PARAMETERS)
    {
        return $this->getIf(['UserId'], $endpoint, $collect, $parameters);
    }
}

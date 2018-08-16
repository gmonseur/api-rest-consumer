<?php

// ------------------------------------------------------------------------

/**
 * Formats Helpers
 */

// ------------------------------------------------------------------------


/**
 * Is json
 * @param mixed ...$args
 * @return bool
 */
if (!function_exists('is_JSON')) {
    function is_JSON($json)
    {
        json_decode($json);
        return (json_last_error() === JSON_ERROR_NONE);
    }
}

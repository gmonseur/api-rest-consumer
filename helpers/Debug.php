<?php

// ------------------------------------------------------------------------

/**
 * Debug Helpers
 */

// ------------------------------------------------------------------------

/**
 * Trace
 *
 * Retourne un print_r formaté
 *
 * @param array $array
 */

if (!function_exists('trace')) {
    function trace($var, $isOpen = false, $mode_debug = MODE_DEBUG)
    {
        if ($mode_debug) {
            $debug = debug_backtrace();
            $lUniqId = uniqid(md5(rand()));

            echo '<div style="margin-left: 10px">
                <div>
                    <a style="display:block;padding:4px;text-decoration:none;outline:none" href="javascript:void(0);" onClick="var div = document.getElementById(\'printr_' . $lUniqId . '\');
                    if (div.style.display!=\'none\') div.style.display = \'none\'; else div.style.display = \'block\';">
                        <p style="color:orange;cursor:pointer"><span> [-]</span> <strong>' . $debug[0]['file'] . ' </strong> ---line.' . $debug[0]['line'] . '</p>
                    </a>
                </div>
                <div style="display:' . ($isOpen ? 'block' : 'none') . ';" id="printr_' . $lUniqId . '">
                    <pre>';
            echo @print_r($var);
            echo '</pre>
                    </div>
              </div>';
        }
    }
}

/**
 * Profiling
 * Call this at each point of interest, passing a descriptive string
 * @param $str
 */
if (!function_exists('prof_flag')) {
    function prof_flag($str)
    {
        global $prof_timing, $prof_names;
        $prof_timing[] = microtime(true);
        $prof_names[] = $str;
    }
}


/**
 * Profiling
 * Call this when you're done and want to see the results
 */
if (!function_exists('prof_print')) {
    function prof_print()
    {
        global $prof_timing, $prof_names;
        $size = count($prof_timing);
        $prof_tot_time = 0;
        echo "-- Profiling ---------------------------------<br>";
        for ($i = 0; $i < $size - 1; $i++) {
            echo "<b>{$prof_names[$i]}</b><br>";
            $prof_time_fork = $prof_timing[$i + 1] - $prof_timing[$i];
            echo sprintf("&nbsp;&nbsp;&nbsp;&nbsp;%f<br>", $prof_time_fork);
            $prof_tot_time = $prof_tot_time + $prof_time_fork;
        }
        echo "<b>{$prof_names[$size-1]}</b><br><br>";
        echo "<b>Total Time</b><br>";
        echo "&nbsp;&nbsp;&nbsp;&nbsp; $prof_tot_time<br>";
        echo "----------------------------------------------<br>";
    }
}



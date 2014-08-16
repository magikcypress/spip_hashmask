<?php
/**
 * MonitorSites
 *
 * @version    1.7.0
 * @copyright  2013
 * @author     Vincent L
 * @licence    GNU/GPL3
 */

function curl_get($href, $header = false, $body = true, $timeout = 10, $add_agent = true, $status = false) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, $header);
        curl_setopt($ch, CURLOPT_NOBODY, (!$body));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_URL, $href);
        if($add_agent) {
                curl_setopt ($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; spip/; +http://www.spip.net)');
        }

        $result = curl_exec($ch);

        // if size page
        if($status) {
                $result = curl_getinfo($ch, $status);
        }

        curl_close($ch);

        return $result;
}

function updateWebsite($href) {

        $starttime = microtime(true);
 
        $result = curl_get($href, true);

        $latency = (microtime(true) - $starttime);

        $status_code = strtok($result, "\r\n");
        // keep it general
        // $code[1][0] = status code
        // $code[2][0] = name of status code
        $code_matches = array();
        preg_match_all("/[A-Z]{2,5}\/\d\.\d\s(\d{3})\s(.*)/", $status_code, $code_matches);

        if(empty($code_matches[0])) {
                // somehow we dont have a proper response.
                $error = 'no response from server';
                $result = false;
        } else {
                $code = $code_matches[1][0];
                $msg = $code_matches[2][0];

                // All status codes starting with a 4 or higher mean trouble!
                if(substr($code, 0, 1) >= '4') {
                        $error = $code . ' ' . $msg;
                        $result = false;
                } else {
                        $result = true;
                }
        }

        return array('result' => $result, 'latency' => $latency);
}

function updateService($href) {
        $errno = 0;
        $error = 1;
        // save response time
        $starttime = microtime(true);

        $fp = fsockopen($href, '80', $errno, $error, 10);

        $status = ($fp === false) ? false : true;
        $rtime = (microtime(true) - $starttime);

        fclose($fp);

        return $status;
}

function sizePage($href) {

        $poids = curl_get($href, true, true, 10, false, CURLINFO_SIZE_DOWNLOAD);

        if(!$poids)
                $result = false;
        else
                $result = true;

        return array('result' => $result, 'poids' => $poids);
}

function getPageSpeedGoogle($href) {
        $url_pagespeed = 'https://www.googleapis.com/pagespeedonline/v1/runPagespeed?url=';
        $result = curl_get($url_pagespeed.$href, false);
        $result = json_decode($result, false);

        if(isset($result)) {
            $score = $result->{'score'};
            $pagestats = $result->{'pageStats'};
            $minifycss = $result->{'formattedResults'}->{'ruleResults'}->{'MinifyCss'}->{'ruleImpact'};
            $minifycss_msg = $result->{'formattedResults'}->{'ruleResults'}->{'MinifyCss'}->{'urlBlocks'};
            foreach ($minifycss_msg[0]->{'header'} as $key => $value) {
                if($key == 'format') $minifycss_msg = $value;
            }
            $minifyhtml = $result->{'formattedResults'}->{'ruleResults'}->{'MinifyHTML'}->{'ruleImpact'};
            $minifyhtml_msg = $result->{'formattedResults'}->{'ruleResults'}->{'MinifyHTML'}->{'urlBlocks'};
            foreach ($minifyhtml_msg[0]->{'header'} as $key => $value) {
                if($key == 'format') $minifyhtml_msg = $value;
            }
            $minifyjavascript = $result->{'formattedResults'}->{'ruleResults'}->{'MinifyJavaScript'}->{'ruleImpact'};
            $minifyjavascript_msg = $result->{'formattedResults'}->{'ruleResults'}->{'MinifyJavaScript'}->{'urlBlocks'};
            foreach ($minifyjavascript_msg[0]->{'header'} as $key => $value) {
                if($key == 'format') $minifyjavascript_msg = $value;
            }   
        }
        
        return array('score' => $score, 
        			'pagestats' => $pagestats, 
        			'minifycss' => $minifycss,
                    'minifycss_msg' => $minifycss_msg,
        			'minifyhtml' => $minifyhtml,
                    'minifyhtml_msg' => $minifyhtml_msg,
        			'minifyjavascript' => $minifyjavascript,
                    'minifyjavascript_msg' => $minifyjavascript_msg);
}
<?php
require_once '_inc.php';

require_once 'HTTP/ConditionalGet.php';

function test_HTTP_ConditionalGet()
{
    global $thisDir;
    
    $lmTime = time() - 900;
    $gmtTime = gmdate('D, d M Y H:i:s \G\M\T', $lmTime);
    
    $tests = array(
        array(
            'desc' => 'client has valid If-Modified-Since'
            ,'inm' => null
            ,'ims' => $gmtTime
            ,'exp' => array(
            	'Last-Modified' => $gmtTime
                ,'ETag' => "\"{$lmTime}pri\""
                ,'Cache-Control' => 'max-age=0, private, must-revalidate'
                ,'_responseCode' => 'HTTP/1.0 304 Not Modified'
                ,'isValid' => true
            )
        )
        ,array(
            'desc' => 'client has valid If-Modified-Since with trailing semicolon'
            ,'inm' => null
            ,'ims' => $gmtTime . ';'
            ,'exp' => array(
            	'Last-Modified' => $gmtTime
                ,'ETag' => "\"{$lmTime}pri\""
                ,'Cache-Control' => 'max-age=0, private, must-revalidate'
                ,'_responseCode' => 'HTTP/1.0 304 Not Modified'
                ,'isValid' => true
            )
        )
        ,array(
            'desc' => 'client has valid ETag'
            ,'inm' => "\"badEtagFoo\", \"{$lmTime}pri\""
            ,'ims' => null
            ,'exp' => array(
                'Last-Modified' => $gmtTime
                ,'ETag' => "\"{$lmTime}pri\""
                ,'Cache-Control' => 'max-age=0, private, must-revalidate'
                ,'_responseCode' => 'HTTP/1.0 304 Not Modified'
                ,'isValid' => true
            )
        )
        ,array(
            'desc' => 'no conditional get'
            ,'inm' => null
            ,'ims' => null
            ,'exp' => array(
                'Last-Modified' => $gmtTime
                ,'ETag' => "\"{$lmTime}pri\""
                ,'Cache-Control' => 'max-age=0, private, must-revalidate'
                ,'isValid' => false
            )
        )
        ,array(
            'desc' => 'client has invalid ETag'
            ,'inm' => '"' . ($lmTime - 300) . 'pri"'
            ,'ims' => null
            ,'exp' => array(
                'Last-Modified' => $gmtTime
                ,'ETag' => "\"{$lmTime}pri\""
                ,'Cache-Control' => 'max-age=0, private, must-revalidate'
                ,'isValid' => false
            )
        )
        ,array(
            'desc' => 'client has invalid If-Modified-Since'
            ,'inm' => null
            ,'ims' => gmdate('D, d M Y H:i:s \G\M\T', $lmTime - 300)
            ,'exp' => array(
                'Last-Modified' => $gmtTime
                ,'ETag' => "\"{$lmTime}pri\""
                ,'Cache-Control' => 'max-age=0, private, must-revalidate'
                ,'isValid' => false
            )
        )
    );
    
    foreach ($tests as $test) {
        // setup env
        if (null === $test['inm']) {
            unset($_SERVER['HTTP_IF_NONE_MATCH']);
        } else {
            $_SERVER['HTTP_IF_NONE_MATCH'] = get_magic_quotes_gpc()
                ? addslashes($test['inm'])
                : $test['inm'];;
        }
        if (null === $test['ims']) {
            unset($_SERVER['HTTP_IF_MODIFIED_SINCE']);
        } else {
            $_SERVER['HTTP_IF_MODIFIED_SINCE'] = $test['ims'];
        }
        $exp = $test['exp'];
        
        $cg = new HTTP_ConditionalGet(array(
            'lastModifiedTime' => $lmTime
        ));
        $ret = $cg->getHeaders();
        $ret['isValid'] = $cg->cacheIsValid;
        
        $passed = assertTrue($exp == $ret, 'HTTP_ConditionalGet : ' . $test['desc']);
        
        if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
            echo "\n--- INM = {$test['inm']} / IMS = {$test['ims']}\n";
            echo "Expected = " . preg_replace('/\\s+/', ' ', var_export($exp, 1)) . "\n";
            echo "Returned = " . preg_replace('/\\s+/', ' ', var_export($ret, 1)) . "\n\n";
        }
    }
}

test_HTTP_ConditionalGet();
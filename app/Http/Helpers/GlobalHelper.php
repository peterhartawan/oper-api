<?php

namespace App\Http\Helpers;

class GlobalHelper 
{
    public static function getResourceError($resource_lang_key, $params = [], $code = 500, $status_code = 500)
    {
        $result = [
            'message'       => $resource_lang_key,
            'code'          => $code,
            'status_code'   => $status_code];
        
        $resource_message = trans($resource_lang_key, $params);
        if (!empty($resource_message))
        {
            if (is_array($resource_message))
            {
                if (array_key_exists('message', $resource_message))
                    $result['message'] = trans($resource_lang_key . '.message', $params);
                if (array_key_exists('code', $resource_message))
                    $result['code'] = $resource_message['code'];
                if (array_key_exists('status_code', $resource_message))
                    $result['status_code'] = $resource_message['status_code'];
            }
            else
                $result['message'] = $resource_message;
        }
        
        if ($result['status_code'] < 100 || $result['status_code'] >= 600)
            $result['status_code'] = 500;
        
        return $result;
    }

    public static function replace_hp($nohp)
    {
        // kadang ada penulisan no hp 0811 239 345
        $nohp = str_replace(" ","",$nohp);
        // kadang ada penulisan no hp (0274) 778787
        $nohp = str_replace("(","",$nohp);
        // kadang ada penulisan no hp (0274) 778787
        $nohp = str_replace(")","",$nohp);
        // kadang ada penulisan no hp 0811.239.345
        $nohp = str_replace(".","",$nohp);

        // cek apakah no hp mengandung karakter + dan 0-9
        if(!preg_match('/[^+0-9]/',trim($nohp))){
            // cek apakah no hp karakter 1-3 adalah +62
            if(substr(trim($nohp), 0, 2)=='62'){
                $nohp = trim($nohp);
            }
            // cek apakah no hp karakter 1 adalah 0
            elseif(substr(trim($nohp), 0, 1)=='0'){
                $nohp = '62'.substr(trim($nohp), 1);
            }
        }
        
        return $nohp;
    }

    public static function generateTrxId()
	{
        list($usec, $sec)		= explode(" ", microtime());
        $usec					= substr($usec, 0, 8);
        $rand                   = mt_rand(1,100);
        $new_microtime			= ($sec + $usec + $rand) * 10000;

		$base			= "123ZYXWVUTSRQKJIHGFEDCBA456ABCDEFGHIJKLMNOPQRSTUVWXYZ789RCMGH";
		$length			= strlen($base);
        $out			= '';

		while($new_microtime > $length - 1)
		{
            $fmod       = intval(fmod($new_microtime, $length));
            if (isset($base[$fmod]))
                $out    = $base[$fmod] . $out;

			$new_microtime	= intval(floor($new_microtime / $length));
        }
        
        $code = isset($base[$new_microtime]) ? $base[$new_microtime] . $out : $out;
        return strtoupper(date('M').$code);
	}
}
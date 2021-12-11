<?php

namespace App\Services;


class HelperFunctions
{
    static public function base64Decoder($file) {
            $base64_string =  $file;
            $extension = explode('/', explode(':', substr($base64_string, 0, strpos($base64_string, ';')))[1])[1];
            $replace = substr($base64_string, 0, strpos($base64_string, ',')+1);
            $image = str_replace($replace, '', $base64_string);
            $image = str_replace(' ', '+', $image);
            $fileName = time().'.'.$extension;
            return [$fileName,$image];
        }
}

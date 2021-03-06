<?php

class CURLManager
{
    public function __construct() {
    }

    public function getContents($url, $never_cache_again = FALSE, $skip_cache = FALSE, $cachetime = 604800){
        return $this->cache_url($url, $skip_cache, $never_cache_again, $cachetime);
    }

    private function cache_url($url,  $never_cache_again, $skip_cache, $cachetime) {
        $where = __DIR__."/cache";
        if ( ! is_dir($where)) {
            mkdir($where);
        }
        $hash = md5($url);
        $file = "$where/$hash.cache";
        $mtime = 0;
        if (file_exists($file)) {
            $mtime = filemtime($file);
        }
        $filetimemod = $mtime + $cachetime;
        if ($filetimemod < time() OR $skip_cache OR $never_cache_again) {
            $data = file_get_contents($url);
            if ($data AND ! $skip_cache) {
                file_put_contents($file, $data);
            }
        } else {
            if(file_exists($file)){
                $data = file_get_contents($file);
            }else{
                $data = $this->cache_url($url, true, false, $cachetime);
            }
        }        
        return $data;
    }
}


?>
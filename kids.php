<?php
ob_start();

class BotDetecter
{
    protected static $responce;
    
    public static function isBot(){
        $responce = self::getResponce();
        return isset($responce['is_bot'])?$responce['is_bot']:true;
    }
    
    public static function isNotBot(){
        $responce = self::getResponce();
        return isset($responce['is_bot'])?!$responce['is_bot']:false;
    }
    
    public static function getResponce(){
        if(!isset(self::$responce)){
            $flow           = "15G7tK";
            $uniq           = false;
            $useragent      = "";
            $acceptLanguage = null;
            $token          = "4r1TyKJuhPEfez5dRQixK1_qy27YOQD7";
            $url            = "http://3934428382.peerclicktrk.com/cloaking";
            $cookie         = null;
            $cid            = null;
            $referer        = null;
            $get            = json_encode($_GET);

            if (!function_exists('getallheaders')){ 
                $headers = array (); 
                foreach ($_SERVER as $name => $value){ 
                    if (substr($name, 0, 5) == 'HTTP_'){ 
                        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value; 
                    } 
                } 
            }else{
                $headers = getallheaders();
            }
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
            if(isset($headers['Cookie']['PHPSESSID'])){
                $uniq = true;
            }
            if(isset($headers['User-Agent'])){
                $useragent = $headers['User-Agent'];
            } else {
                $useragent = $_SERVER['HTTP_USER_AGENT'];
            }
            if(isset($headers['Accept-Language'])){
                $acceptLanguage = $headers['Accept-Language'];
            }
            $cookies = [];
            if(isset($headers['Cookie'])){
                $cookie = $headers['Cookie'];
                $cookie_arr = explode(";",$headers['Cookie']);
                foreach($cookie_arr as $key=>$item){
                    $cookie_arr[$key] = explode("=",$item);
                    $cookies[$cookie_arr[$key][0]] = $cookie_arr[$key][1];
                    if($cookie_arr[$key][0]=="peerclickcid")
                        $cid = $cookie_arr[$key][1];
                }
            }
            if(isset($_SERVER["HTTP_REFERER"])){
                $referer = $_SERVER["HTTP_REFERER"];
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "{$url}?token={$token}");
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,true); 

            $params = [
                "flow"            => $flow,
                "uniq"            => $uniq,
                "useragent"       => $useragent,
                "accept_language" => $acceptLanguage,
                "ip"              => $ip,
                "cookie"          => json_encode($cookies),
                "headers"         => $headers,
                "cid"             => $cid,
                "referer"         => $referer,
                "get_params"      => $get
            ];

            curl_setopt($ch,CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch,CURLOPT_POST, true);
            $responce = curl_exec($ch);
            $responce = json_decode($responce,true);
            curl_close($ch);
            
            if($responce){
                setcookie($flow."o", 1, time()+1800);
                if(isset($responce['cid']))
                    setcookie("peerclickcid", $responce['cid'], time()+1800);
                if(isset($responce['utm']))
                    setcookie("peerclickutm", $responce['utm'], time()+1800);

                $str = "";
                foreach($_GET as $key=>$item){
                    $str .= "{$key}=$item&";
                }
                $str = preg_replace("/\&$/","",$str);
                if(isset($responce['redirect_to'])){
                    preg_match("/\?/",$responce['redirect_to'],$out);
                    if(isset($out[0]))
                        $responce['redirect_to'] .= "&".$str;
                    else
                        $responce['redirect_to'] .= "?".$str;
                }
                self::$responce = $responce;
            }
        }
        return self::$responce;
    }
    
    public static function redirectIfNotBot(){
        if(!self::isBot()&&isset(self::$responce['redirect_to'])){
            header('HTTP/1.1 301 Moved Permanently');
            header("Location: ".self::$responce['redirect_to']);
            ob_end_flush();
        }
    }
    
    public static function redirectIfBot(){
        if(self::isBot()&&isset(self::$responce['redirect_to'])){
            header('HTTP/1.1 301 Moved Permanently');
            header("Location: ".self::$responce['redirect_to']);
            ob_end_flush();
        }
    }
}

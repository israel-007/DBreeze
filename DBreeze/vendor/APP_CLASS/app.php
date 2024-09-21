<?php

class APP
{

    static private $encrypt_method = "AES-256-CBC";
    static private $secret_key = "92f9-f2c1-fh47";
    static private $secret_iv = 'LO3P-K8V4-L9g4';
    static private $hash_key = 'sha256';


    static private $YM_singular = array(
        365 * 24 * 60 * 60 => 'year',
        30 * 24 * 60 * 60 => 'month',
        24 * 60 * 60 => 'day',
        60 * 60 => 'hour',
        60 => 'minute',
        1 => 'second'
    );

    static private $YM_plural = array(
        'year' => 'years',
        'month' => 'months',
        'day' => 'days',
        'hour' => 'hours',
        'minute' => 'minutes',
        'second' => 'seconds'
    );

    static public function hashPassword(string $var)
    {

        $options = [
            'cost' => 12
        ];

        $hash = password_hash($var, PASSWORD_BCRYPT, $options);

        return $hash;

    }

    static public function verifyPassword(string $var, string $hash)
    {

        return password_verify($var, $hash);

    }

    static public function validateString(string $var, bool $tags = false)
    {

        $clean = trim($var);

        $clean = preg_replace('/[^a-zA-Z0-9\s,@._\-\/' . ($tags ? '<>' : '') . ']/', '', $clean);

        return $clean;

    }

    public static function validate(array $data): array
    {
        foreach ($data as $value => $ruleSet) {
            $ruleArray = explode(',', $ruleSet);

            foreach ($ruleArray as $rule) {
                [$ruleName, $ruleCondition] = explode('|', trim($rule));

                // Check validation for each rule
                switch (strtoupper($ruleName)) {
                    case 'LENGTH':
                        if (strlen($value) < intval($ruleCondition)) {
                            return [
                                false,
                                'message' => "The input '$value' must be at least $ruleCondition characters long."
                            ];
                        }
                        break;

                    case 'TYPE':
                        if (strtoupper($ruleCondition) === 'PASSWORD') {
                            // Ensure the password has at least one alphabetic and one numeric character
                            if (!preg_match('/[a-zA-Z0-9]/', $value)) {
                                return [
                                    false,
                                    'message' => "Your password must contain alphanumeric characters."
                                ];
                            }
                        } elseif (strtoupper($ruleCondition) === 'EMAIL') {
                            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                                return [
                                    false,
                                    'message' => "The email '$value' is not a valid email address."
                                ];
                            }
                        } elseif (strtoupper($ruleCondition) === 'PHONENUMBER') {
                            if (!preg_match('/^\d{10,11}$/', $value)) {
                                return [
                                    false,
                                    'message' => "The phone number '$value' must be a valid 10 or 11-digit number."
                                ];
                            }
                        } elseif (strtoupper($ruleCondition) === 'STRING') {
                            if (!is_string($value)) {
                                return [
                                    false,
                                    'message' => "The input '$value' must be a valid string."
                                ];
                            }
                        }
                        break;

                    case 'SPECIALCHARS':
                        if (strtoupper($ruleCondition) === 'TRUE' && !preg_match('/[\W_]/', $value)) {
                            return [
                                false,
                                'message' => "The input '$value' must contain at least one special character."
                            ];
                        }
                        break;

                    case 'NUMERIC':
                        if (!is_numeric($value)) {
                            return [
                                false,
                                'message' => "The input '$value' must be a number."
                            ];
                        }
                        break;

                    case 'ALPHANUMERIC':
                        if (!ctype_alnum($value)) {
                            return [
                                false,
                                'message' => "The input '$value' must be alphanumeric."
                            ];
                        }
                        break;

                    case 'REQUIRED':
                        if (strtoupper($ruleCondition) === 'TRUE' && empty($value)) {
                            return [
                                false,
                                'message' => "The input '$value' is required."
                            ];
                        }
                        break;

                    default:
                        return [
                            false,
                            'message' => "Unknown validation rule: $ruleName."
                        ];
                }
            }
        }

        // All validations passed
        return [
            true,
            'message' => 'success'
        ];
    }

    static public function dump($data)
    {

        return var_dump($data);

    }

    static public function match(string $key, array $array)
    {

        $keys = array_keys($array);

        if (in_array($key, $keys)) {

            return $array[$key];

        } else {

            if (in_array('default', $keys)) {

                return $array['default'];

            } else {

                return null;

            }

        }

    }

    static public function encrypt(string $var)
    {

        $key = hash(self::$hash_key, self::$secret_key);
        $iv = substr(hash(self::$hash_key, self::$secret_iv), 0, 16);

        $output = openssl_encrypt($var, self::$encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);

        return $output;

    }

    static public function decrypt(string $var)
    {

        $key = hash(self::$hash_key, self::$secret_key);
        $iv = substr(hash(self::$hash_key, self::$secret_iv), 0, 16);
        $output = openssl_decrypt(base64_decode($var), self::$encrypt_method, $key, 0, $iv);

        return $output;

    }

    static public function CURL($url)
    {

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        if (is_array($url)) {

            $i = 0;

            foreach ($url as $uri) {

                curl_setopt($ch, CURLOPT_URL, $uri);

                $result[$i++] = curl_exec($ch);

            }

        } else {

            curl_setopt($ch, CURLOPT_URL, $url);

            $result = curl_exec($ch);

        }

        return $result;

    }

    static public function date($date, $format = 'D d, M Y')
    {

        $hastack = array('d', 'D', 'j', 'l', 'N', 'S', 'w', 'z', 'W', 'F', 'm', 'M', 'n', 't', 'L', 'o', 'Y', 'y', 'a', 'A', 'B', 'g', 'G', 'h', 'H', 'i', 's', 'u', 'e', 'I', 'O', 'P', 'T', 'Z', 'c', 'r', 'U');

        $_date['date'] = $date;
        if (empty($_date['date'])) {
            return '';
        }

        $array = APP::toArray($_date['date'], ' ');
        $array = array_merge(APP::toArray($_date['date'], '-'), $array);
        $array = array_merge(APP::toArray($_date['date'], ','), $array);
        $array = array_merge(APP::toArray($_date['date'], '/'), $array);
        $array = array_merge(APP::toArray($_date['date'], '_'), $array);

        $i = 0;

        foreach ($array as $data) {

            if (in_array($data, $hastack)) {

                $i++;

            }
        }

        if ($i > 0) {

            return date($_date['date']);

        } else {

            if (!is_numeric($_date['date'])) {
                $_date['date'] = strtotime($_date['date']);
            }

            return date($format, $_date['date']);

        }












    }

    static public function AddMonthsToDate(string $dateString, int $monthsToAdd)
    {

        $date = new DateTime($dateString);

        $date->modify("+" . $monthsToAdd . " months");

        return $date->format('Y-m-d');
    }

    static public function AddDaysToDate(string $dateString, int $daysToAdd)
    {

        $date = new DateTime($dateString);

        $date->modify("+" . $daysToAdd . " days");

        return $date->format('Y-m-d');
    }

    static public function dateDiff(string $date1, string $date2)
    {
        $datetime1 = new DateTime($date1);
        $datetime2 = new DateTime($date2);

        if ($datetime2 < $datetime1) {

            return 0;

        }

        $interval = $datetime1->diff($datetime2);

        return $interval->days;
    }

    static public function time12($time24)
    {

        $time = DateTime::createFromFormat('Hi', $time24);

        if (!$time) {
            return "Invalid time format";
        }

        return $time->format('h:i A');
    }

    static public function hourDiff($time1, $time2)
    {
        // Create DateTime objects from the 24-hour format time strings
        $datetime1 = DateTime::createFromFormat('Hi', $time1);
        $datetime2 = DateTime::createFromFormat('Hi', $time2);

        // Check if the DateTime objects are created successfully
        if (!$datetime1 || !$datetime2) {
            return "Invalid time format";
        }

        // Calculate the difference between the two DateTime objects
        $interval = $datetime1->diff($datetime2);

        // Return the difference in hours
        return $interval->h + ($interval->days * 24); // Add days to handle time differences that span over midnight
    }

    static public function toArray(string $var, string $separator = null, mixed $limit = null)
    {

        $separator = ($separator == null) ? ',' : $separator;
        $limit = ($limit == null) ? 0 : $limit;

        if ($limit !== 0) {

            return explode($separator, $var, $limit);

        } else {

            return explode($separator, $var);

        }

    }

    static public function toString(array $var, string $separator = null)
    {

        if ($separator != null) {

            return implode($separator, $var);

        } else {

            return implode($var);

        }

    }

    static public function toBool(string $var)
    {

        return filter_var($var, FILTER_VALIDATE_BOOLEAN);

    }

    static public function money(int $int, int $decimal = 0, string $decimal_separator = '.', string $thousand_separator = ',')
    {

        $number = number_format($int, $decimal, $decimal_separator, $thousand_separator);

        return $number;

    }

    static public function Curpage()
    {

        $scriptPath = $_SERVER['SCRIPT_FILENAME'];

        $page_name = basename($scriptPath);

        $extenssions = array('.php', '.html');

        $strip_name = str_replace($extenssions, '', $page_name);

        return $strip_name;
    }
    // -- CURRENT PAGE NAME --

    // JSON METHODS ------------START----------------

    static public function JsonPush($data, array $keys, string $json = '{}')
    {

        // if(empty($json)){
        //     $json = "{}";
        // }

        $key = $keys;

        $decodedJson = json_decode($json, true);

        foreach ($data as $ke => $dat) {
            $decodedJson[$key[$ke]] = $dat;
        }

        // foreach ($data as $key => $value) {

        //     if (array_key_exists($key, $decodedJson)) {

        //         $decodedJson[][$key] = $value;

        //     } else {

        //         $decodedJson[][$key] = $value;

        //     }

        // }

        $jsonUpdated = json_encode($decodedJson);

        return $jsonUpdated;

    }

    static public function JsonGet(string $json, string $key)
    {

        $decodedJson = json_decode($json, true);

        if (array_key_exists($key, $decodedJson)) {

            return $decodedJson[$key];

        } else {

            return null;

        }
    }

    static public function JsonPull(string $json, string $keyToRemove)
    {
        $decodedJson = json_decode($json, true);

        if (array_key_exists($keyToRemove, $decodedJson)) {

            unset($decodedJson[$keyToRemove]);

            $jsonWithRemovedItem = json_encode($decodedJson);

            return $jsonWithRemovedItem;

        } else {

            return $json;

        }
    }

    static public function JsonCount(string $json)
    {

        $decodedJson = json_decode($json, true);

        $itemCount = count($decodedJson);

        return $itemCount;
    }

    // JSON METHODS ------------END----------------


    // COOKIE METHODS -----------START----------------

    static public function Cookie(string $key, string $value, int $expire = null, string $path = null, $domain = null, bool $secure = null, bool $httponly = null)
    {

        $expire = ($expire == null) ? 3600 * 24 : $expire;
        $path = ($path == null) ? "/" : $path;
        $domain = ($domain == null) ? "" : $domain;
        $secure = ($secure == null) ? false : $secure;
        $httponly = ($httponly == null) ? false : $httponly;

        $key = APP::encrypt($key);
        $value = APP::encrypt($value);

        $expire_time = time() + $expire;

        setcookie($key, $value, $expire_time, $path, $domain, $secure, $httponly);

    }

    static public function GetCookie(string $key)
    {

        $key = APP::encrypt($key);

        if (isset($_COOKIE[$key])) {

            $value = APP::decrypt($_COOKIE[$key]);

            return $value;

        } else {

            return null;

        }

    }

    static public function PullCookie(string $key, string $path = null)
    {
        $path = ($path == null) ? '/' : $path;

        $key = APP::encrypt($key);

        if (isset($_COOKIE[$key])) {

            setcookie($key, "", time() - 3600, $path);

            return true;

        } else {

            return null;

        }

    }

    // COOKIE METHODS -----------END----------------

    static public function userAgent()
    {

        $useragent = $_SERVER['HTTP_USER_AGENT'];

        $url = 'https://api.apicagent.com/?ua=' . rawurlencode($useragent);

        $result = APP::CURL($url);

        return $result;

    }

    static public function isConnected()
    {
        $url = 'https://www.google.com';

        $headers = @get_headers($url);

        if ($headers && strpos($headers[0], '200') !== false) {

            return true;

        } else {

            return false;

        }
    }

    static public function logError($message, $file_path = null)
    {

        $file_path = ($file_path == null) ? 'err_log.txt' : $file_path;

        // Get the current date and time
        $timestamp = date('Y-m-d H:i:s');

        // Format the log entry
        $log_entry = "[$timestamp] $message\n";

        // Write the log entry to the file
        file_put_contents($file_path, $log_entry, FILE_APPEND);
    }

    //------------------------------------------

    static public function imageProperties(string $imagePath)
    {

        // Check if the file exists
        if (!file_exists($imagePath)) {
            return false;
        }

        // Get the image dimensions
        $imageSize = getimagesize($imagePath);

        // Check if the getimagesize function was successful
        if ($imageSize === false) {
            return false;
        }

        // Return the width and height as an associative array
        return [
            'width' => $imageSize[0],
            'height' => $imageSize[1]
        ];
    }

    static public function redirect(string $url, int $seconds)
    {

        return header('refresh:' . $seconds . ';url=' . $url);

    }

    static public function uniqueId($length = 16)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        $bytes = random_bytes(16);

        for ($i = 0; $i < strlen($bytes); $i++) {
            $randomString .= $characters[ord($bytes[$i]) % $charactersLength];
        }

        if (is_int($length) && $length > 0) {
            $randomString = substr($randomString, 0, $length);
        }

        return $randomString;
    }

    static public function urlParse(string $part = null, $url = null)
    {

        // If no URL is provided, construct the current URL
        if ($url === null) {
            $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $uri = $_SERVER['REQUEST_URI'];
            $url = $scheme . '://' . $host . $uri;
        }

        // Parse the URL into its components
        $parsedUrl = parse_url($url);

        // If no specific part is requested, return the full URL
        if ($part === null) {
            return $url;
        }

        // Return the requested part of the URL if it exists
        return $parsedUrl[$part] ?? null;
    }

    static public function error_report(int $level = 0)
    {

        error_reporting($level);

    }






    static public function Cart($data)
    {

        if (empty(APP::GetCookie('APP-CART'))) {

            $cart = '{}';

        } else {

            $cart = APP::GetCookie('APP-CART');

        }

        $decodedCart = json_decode($cart, true);

        foreach ($data as $dat) {
            $decodedCart[] = $dat;
        }

        $CartUpdated = json_encode($decodedCart);

        APP::cookie('APP-CART', $CartUpdated);

    }
    static public function CartUpdate($key, $value, $new_item)
    {

        $data = json_decode(APP::GetCookie('APP-CART'), true);

        foreach ($data as &$item) {
            if ($item[$key] === $value) {
                $item = array_merge($item, $new_item);
            }
        }

        $CartUpdated = json_encode($data);

        APP::cookie('APP-CART', $CartUpdated);

    }
    static public function CartPull($key, $value)
    {

        $data = json_decode(APP::GetCookie('APP-CART'), true);

        $data = array_filter($data, function ($item) use ($key, $value) {

            return $item[$key] !== $value;

        });

        $CartUpdated = json_encode($data);

        APP::cookie('APP-CART', $CartUpdated);

    }
    static public function CartGet($key, $value)
    {

        $data = json_decode(APP::GetCookie('APP-CART'), true);

        $result = array_filter($data, function ($item) use ($key, $value) {
            return isset($item[$key]) && $item[$key] == $value;
        });

        $CartUpdated = json_encode($result);

        return $CartUpdated;

    }
    static public function CartAll()
    {

        // $data = json_decode(APP::GetCookie('APP-CART'), true);

        return APP::GetCookie('APP-CART');

    }











    static public function view($doc)
    {

        $folder = glob('views' . '/*');

        $i = 0;

        foreach ($folder as $file) {

            $str_file = str_replace('views/', '', $file);

            if ($str_file == 'view.' . $doc) {

                require_once($file);

            }

        }

    }
}

?>
<?php

CLASS JSON{

    static public function select($json, array $parameters){

        $data = json_decode($json, true);

        if (!is_array($data)) {
            return json_encode(["error" => "Invalid JSON data"]);
        }

        $results = array_filter($data, function ($item) use ($parameters) {
            foreach ($parameters as $key => $value) {
                if (strpos($value, '||') !== false) {
                    $values = array_map('trim', explode('||', $value));
                    if (!isset($item[$key]) || !in_array($item[$key], $values)) {
                        return false;
                    }
                } else {
                    if (!isset($item[$key]) || $item[$key] != $value) {
                        return false;
                    }
                }
            }
            return true;
        });

        return json_encode(array_values($results));

    }

    static public function distinct($json, string $key){

        $data = json_decode($json, true);

        if (!is_array($data)) {
            return json_encode(["error" => "Invalid JSON data"]);
        }

        $values = array_column($data, $key);
        $distinctValues = array_unique($values);

        return json_encode(array_values($distinctValues));

    }

    static public function search($json, array $parameters){

        $data = json_decode($json, true);

        if (!is_array($data)) {
            return json_encode(["error" => "Invalid JSON data"]);
        }

        $results = array_filter($data, function ($item) use ($parameters) {
            foreach ($parameters as $key => $value) {
                $values = array_map('trim', explode('||', $value));
                $matchFound = false;

                foreach ($values as $val) {
                    if (isset($item[$key])) {
                        if (stripos($item[$key], $val) === 0 || strripos($item[$key], $val) === (strlen($item[$key]) - strlen($val))) {
                            $matchFound = true;
                            break;
                        }
                    }
                }

                if (!$matchFound) {
                    return false;
                }
            }
            return true;
        });

        return json_encode(array_values($results));

    }

    static public function count($json){

        $data = json_decode($json, true);

        if (!is_array($data)) {
            return json_encode(["error" => "Invalid JSON data"]);
        }

        return count($data);

    }

}


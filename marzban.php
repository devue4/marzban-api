<?php

class API {
    private $base_url;
    private $username;
    private $password;
    private $access_token;

    public function __construct($base_url, $username, $password) {
        $this->base_url = $base_url;
        $this->username = $username;
        $this->password = $password;

        $url = $this->base_url . '/api/admin/token';

        $data = array(
            'username' => $this->username,
            'password' => $this->password
        );

        $response = $this->send_request($url, 'POST', $data, array('Content-Type: application/x-www-form-urlencoded'));

        if ($response['status_code'] == 200) {
            $this->access_token = $response['data']['access_token'];
        } else {
            echo "Failed to obtain access token. Status code: " . $response['status_code'] . ' ' . $response['data'];
            $this->access_token = null;
        }
    }

    public function get_users() {
        $url = $this->base_url . '/api/users';

        $response = $this->send_request($url, 'GET');
        return json_decode($response['data'], true);
    }

    public function get_user($username) {
        $url = $this->base_url . '/api/user/' . $username;
        $response = $this->send_request($url, 'GET');
        return json_decode($response['data'], true);
    }

    public function add_user($username, $traffic = 0, $day = 30) {
        $url = $this->base_url . '/api/user';
        $data = array(
            "username" => $username,
            "status" => "on_hold",
            "data_limit" => $traffic * 1073741824,
            "data_limit_reset_strategy" => "no_reset",
            "proxies" => array("vless" => array("id")),
            "inbounds" => array("vless" => array("VLESS_INBOUND")),
            "on_hold_expire_duration" => $day * 86400
        );

        $response = $this->send_request($url, 'POST', json_encode($data), array('Content-Type: application/json'));
        return json_decode($response['data'], true);
    }

    public function delete_user($username) {
        $url = $this->base_url . '/api/user/' . $username;
        $response = $this->send_request($url, 'DELETE');
        return json_decode($response['data'], true);
    }

    public function edit_user($username, $status = 'active', $traffic = null, $day = null) {
        $url = $this->base_url . '/api/user/' . $username;

        function unix_timestamp($input_date) {
            $target_date = DateTime::createFromFormat("Y-m-d", $input_date, new DateTimeZone('UTC'));
            $epoch = new DateTime("1970-01-01", new DateTimeZone('UTC'));
            $time_difference = $target_date->getTimestamp() - $epoch->getTimestamp();
            return $time_difference;
        }

        $data = array(
            "status" => $status,
            "data_limit" => $traffic * 1073741824,
            "expire" => unix_timestamp($day)
        );

        $response = $this->send_request($url, 'PUT', json_encode($data), array('Content-Type: application/json'));
        return json_decode($response['data'], true);
    }

    private function send_request($url, $method, $data = null, $headers = array()) {
        $headers[] = 'Authorization: Bearer ' . $this->access_token;

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            if ($method === 'POST' || $method === 'PUT') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }

            $response_data = curl_exec($ch);
            $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return array('status_code' => $status_code, 'data' => $response_data);
        } catch (Exception $e) {
            echo "Something went wrong: " . $e->getMessage();
            return null;
        }
    }
}

$api = new API("https://{SERVER_ADDRESS}:{POSR}","{USERNAME}","{PASSWORD}");
echo $api->add_user("{USERNAME}","{TRAFFIC}","{DAY}");
echo $api->edit_user("{USERNAME}",100,"2024-10-01");
echo $api->delete_user("{USERNAME}");
echo $api->get_user("{USERNAME}");
echo $api->get_users();

?>
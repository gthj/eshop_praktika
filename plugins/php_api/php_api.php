<?php
/**
 * Plugin name: K8s Namespace
 */

if ( ! defined('ABSPATH')) {
    exit;
}

add_action('woocommerce_order_status_processing', function($order_id) {
    $api = getenv('K8S_API');
    $token = getenv('K8S_TOKEN');
    $order = wc_get_order($order_id);

    $cpu = "1";
    $memory = "1Gi";

    foreach ($order->get_items() as $item) {
        foreach ($item->get_meta_data() as $meta) {
            $key = strtolower((string)$meta->key);
            $value = strip_tags((string)$meta->value);

            if (strpos($key, 'cpu') !==false && !empty($value)) {
                $cpu = $value;
            }

            if (strpos($key, 'memory') !==false && !empty($value)) {

                $memory = $value;
            }

        }
    }
    $ns = "namespace-" . intval($order_id);
    $url = rtrim($api, '/') . "/api/v1/namespaces/$ns";

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $token
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);


    $namespace_payload = json_encode([
        "apiVersion" => "v1",
        "kind" => "Namespace",
        "metadata" => ["name" => $ns]
    ]);

    $quota_payload = json_encode([
      "apiVersion" => "v1",
      "kind" => "ResourceQuota",
      "metadata" => ["name" => $ns . "-quota"],
      "spec" => [ 
        "hard" => [
          "requests.cpu" => $cpu,
          "requests.memory" => $memory,
          "limits.cpu" => $cpu,
          "limits.memory" => $memory
        ]                             

      ]
    ]);
    
    $url = rtrim($api, '/') . "/api/v1/namespaces";
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $namespace_payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $token,
            "Content-Type: application/json",
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $result1 = curl_exec($ch);
    $http_code1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    sleep(5);
    $quota_url = rtrim($api, '/') . "/api/v1/namespaces/$ns/resourcequotas";
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $quota_url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $quota_payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $token,
            "Content-Type: application/json",
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $result2 = curl_exec($ch);
    $http_code2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

});

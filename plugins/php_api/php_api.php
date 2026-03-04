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

    /* example payload for ResourceQuota */
    /* https://kubernetes.io/docs/concepts/policy/resource-quotas/ */
    $quota_payload = json_encode([
      "apiVersion" => "v1",
      "kind" => "ResourceQuota",
      "metadata" => ["namespace" => $ns, "name" => $ns . "-quota" ],
      "spec" => [ 
        "hard" => [
          "requests.cpu" => "1",
          "requests.memory" => "1Gi",
          "limits.cpu" => "2",
          "limits.memory" => "2Gi"
        ]                             
      ]
    ]);
    
    $url = rtrim($api, '/') . "/api/v1/namespaces";
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
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

});

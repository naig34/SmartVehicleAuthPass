<?php
/**
 * Cloudinary Upload Helper
 * Uploads an image file to Cloudinary and returns the secure URL.
 * No SDK needed — uses the REST API directly.
 */

function uploadToCloudinary($fileTmpPath, $folder = 'smartvehicle') {
    $cloudName  = getenv('CLOUDINARY_CLOUD_NAME') ?: 'dgrcydx61';
    $apiKey     = getenv('CLOUDINARY_API_KEY')    ?: '391395547747124';
    $apiSecret  = getenv('CLOUDINARY_API_SECRET') ?: 'ecph0f8OEQ2IdjOtdsYiQ6p61OA';

    $timestamp  = time();
    $params     = "folder={$folder}&timestamp={$timestamp}";
    $signature  = sha1($params . $apiSecret);

    $url = "https://api.cloudinary.com/v1_1/{$cloudName}/image/upload";

    $postFields = [
        'file'      => new CURLFile($fileTmpPath),
        'api_key'   => $apiKey,
        'timestamp' => $timestamp,
        'signature' => $signature,
        'folder'    => $folder,
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['success' => false, 'error' => $error];
    }

    $data = json_decode($response, true);

    if (isset($data['secure_url'])) {
        return ['success' => true, 'url' => $data['secure_url']];
    }

    return ['success' => false, 'error' => $data['error']['message'] ?? 'Unknown error'];
}
?>

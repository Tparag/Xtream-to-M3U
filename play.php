<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(http_response_code(204));
}

function generateRandomToken($length = 10) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $token = '';
    for ($i = 0; $i < $length; $i++) {
        $token .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $token;
}

function generateRandomDeviceModel() {
    $models = ['Samsung'=>['Galaxy S21'=>'Android 11','Galaxy S22'=>'Android 12','Galaxy S23'=>'Android 13','Galaxy Note 20'=>'Android 10','Galaxy A52'=>'Android 11','Galaxy Z Fold 3'=>'Android 11'],'Xiaomi'=>['Mi 11'=>'Android 11','Mi 12'=>'Android 12','Redmi Note 10'=>'Android 11','Redmi Note 11'=>'Android 11','Poco F3'=>'Android 11','Poco F4'=>'Android 12'],'Huawei'=>['P40 Pro'=>'Android 10','Mate 40 Pro'=>'Android 10','Nova 8'=>'Android 10','Y9a'=>'Android 10'],'Oppo'=>['Find X3 Pro'=>'Android 11','Find X5 Pro'=>'Android 12','Reno 6'=>'Android 11','Reno 7'=>'Android 11'],'Vivo'=>['X60 Pro'=>'Android 11','X70 Pro'=>'Android 11','V21'=>'Android 11','Y72 5G'=>'Android 11'],'Realme'=>['GT 5G'=>'Android 11','GT 2 Pro'=>'Android 12','Narzo 30'=>'Android 11','8 Pro'=>'Android 11'],'OnePlus'=>['9 Pro'=>'Android 11','10 Pro'=>'Android 12','Nord 2'=>'Android 11','Nord CE 2'=>'Android 11'],'Motorola'=>['Moto G100'=>'Android 11','Edge 20'=>'Android 11','Razr 5G'=>'Android 10','Moto G60'=>'Android 11'],'Lava'=>['Z6'=>'Android 10','Z2 Max'=>'Android 10','Z4'=>'Android 10','Z1'=>'Android 10'],'Infinix'=>['Zero 8'=>'Android 10','Note 10 Pro'=>'Android 11','Hot 10'=>'Android 10','Smart 5'=>'Android 10'],'Tecno'=>['Camon 16'=>'Android 10','Spark 7'=>'Android 11','Pova 2'=>'Android 11','Phantom X'=>'Android 11'],'iQOO'=>['7 Legend'=>'Android 11','Z3'=>'Android 11','5 Pro'=>'Android 10','3'=>'Android 10'],'Nothing'=>['Phone 1'=>'Android 12'],'Asus'=>['ROG Phone 5'=>'Android 11','Zenfone 8'=>'Android 11','ROG Phone 6'=>'Android 12','Zenfone 9'=>'Android 12'],'Lenovo'=>['Legion Duel'=>'Android 10','K12 Pro'=>'Android 10','Z6 Pro'=>'Android 9.0','A7'=>'Android 9.0'],'Sony'=>['Bravia X800H'=>'Android 9','Bravia X950H'=>'Android 9','Bravia XR A80J'=>'Android 10','Bravia XR X90J'=>'Android 10','Bravia XR Z9J'=>'Android 10'],'TCL'=>['6-Series R646'=>'Android 11','5-Series S546'=>'Android 11','P725'=>'Android 11','C725'=>'Android 11'],'Hisense'=>['U8H'=>'Android 11','U7H'=>'Android 11','A6H'=>'Android 11','H9G'=>'Android 10','H8G'=>'Android 9'],'Philips'=>['OLED 806'=>'Android 10','The One 8506'=>'Android 10','OLED 936'=>'Android 10','PUS7906'=>'Android 10'],'Sharp'=>['Aquos XLED'=>'Android 10','4T-C70CK1X'=>'Android 9','4T-C50BK1X'=>'Android 9']];

    $randomBrand = array_rand($models);
    $randomModel = array_rand($models[$randomBrand]);
    $androidVersion = $models[$randomBrand][$randomModel];

    return [
        'brand' => $randomBrand,
        'model' => $randomModel,
        'android_version' => $androidVersion
    ];
}

$configPath = 'data/data.json';

if (!file_exists($configPath)) {
    exit("Error: Configuration file not found.");
}

$config = json_decode(file_get_contents($configPath), true);

if (!isset($config['url'], $config['user'], $config['password'])) {
    exit("Error: Invalid configuration file format.");
}

$host = $config['url'];
$username = $config['user'];
$password = $config['password'];

if (empty($_GET['id'])) {
    exit("Error: Missing or invalid 'id' parameter.");
}

$id = urlencode($_GET['id']);
$url = "{$host}/live/{$username}/{$password}/{$id}.m3u8";

$uniqueToken = generateRandomToken();
$deviceModel = generateRandomDeviceModel();

$headers = [
    "User-Agent: OTT Navigator/1.6.7.4 (Linux;" . $deviceModel['android_version'] . "; " . $deviceModel['brand'] . " " . $deviceModel['model'] . ") ExoPlayerLib/2.15.1",
    "Host: " . parse_url($host, PHP_URL_HOST),
    "Connection: Keep-Alive",
    "Accept-Encoding: gzip",
    "X-Unique-Token: " . $uniqueToken,
    "X-Request-ID: " . uniqid(),
    "X-Device-Model: " . $deviceModel['brand'] . " " . $deviceModel['model']
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);
if (!$response) {
    curl_close($ch);
    exit("Error: cURL request failed. " . curl_error($ch));
}

$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$final_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
curl_close($ch);

if ($status_code != 200) {
    exit("Error: Failed to fetch the m3u8 file. HTTP status code: $status_code");
}

$baseUrl = parse_url($final_url, PHP_URL_SCHEME) . '://' . parse_url($final_url, PHP_URL_HOST);
if ($port = parse_url($final_url, PHP_URL_PORT)) {
    $baseUrl .= ":$port";
}

$processedResponse = implode("\n", array_map(function ($line) use ($baseUrl) {
    if (preg_match('#\.ts$#', $line) && !filter_var($line, FILTER_VALIDATE_URL)) {
        return $baseUrl . '/' . ltrim($line, '/');
    }
    return $line;
}, explode("\n", $response)));

header('Content-Type: application/vnd.apple.mpegurl');
header('Content-Disposition: attachment; filename="' . $id . '.m3u8"');
echo trim($processedResponse);
?>

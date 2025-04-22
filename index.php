<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$dataDir = 'data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

$jsonFile = $dataDir . "/data.json";

$isConnected = file_exists($jsonFile);

$show_popup = false;
$popup_message = '';
$popup_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['disconnect'])) {
        if (file_exists($jsonFile)) {          
            $storedData = json_decode(file_get_contents($jsonFile), true) ?: [];
            if (isset($storedData['url'])) {             
                $hostname = parse_url($storedData['url'], PHP_URL_HOST);
                if ($hostname) {              
                    $playlistName = preg_replace('/[^a-zA-Z0-9]/', '', $hostname);
                    $playlistFile = "data/playlist/" . $playlistName . '.m3u';

                    if (file_exists($playlistFile)) {
                        unlink($playlistFile);
                    }
                }
            }

            unlink($jsonFile);
        }

        $show_popup = true;
        $popup_message = 'Disconnected successfully!';
        $popup_type = 'success';
    } else {
        $sanitize = function ($input) {
            return htmlspecialchars(trim($input));
        };

        $data = [
            "url" => $sanitize($_POST['url'] ?? ''),
            "user" => $sanitize($_POST['user'] ?? ''),
            "password" => $sanitize($_POST['password'] ?? '')            
        ];

        file_put_contents($jsonFile, json_encode($data));
        $isConnected = true;
    }
}

$storedData = [];
if ($isConnected && file_exists($jsonFile)) {
    $storedData = json_decode(file_get_contents($jsonFile), true) ?: [];
}

$currentUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$scriptName = basename($_SERVER['SCRIPT_NAME']);
if (empty($scriptName) || $scriptName == "index.php") {    
    $playlistUrl = rtrim($currentUrl, "/") . "/playlist.php";
} else {    
    $playlistUrl = str_replace($scriptName, "playlist.php", $currentUrl);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Xtream Access</title>
    <style>
        body {
            min-height: 100vh;
            height: auto;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #F7F7FF, #E6E6FA);
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            color: #333333;
        }

        .container {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            padding: 30px;
            margin: 20px;
            overflow: auto;
            max-height: 80vh;
            width: 90%;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #FF9999; 
            margin-bottom: 25px;
        }

        .form-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 12px 0;
        }

        .form-group label {
            flex: 1;
            text-align: left;
            font-weight: 600;
            color: #666666;
        }

        .form-group input {
            flex: 2;
            padding: 12px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.5);
            color: #333333;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: #FF9999;
            box-shadow: 0 0 8px rgba(255, 153, 153, 0.3);
            outline: none;
        }

        input::placeholder {
            color: rgba(102, 102, 102, 0.4);
        }

        button.access-btn, button.disconnect {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        button.access-btn {
            background: linear-gradient(45deg, #99E2B7, #A3BFFA);
            color: #333333;
        }

        button.access-btn:hover {
            background: linear-gradient(45deg, #80D4AA, #8C9EFF);
            box-shadow: 0 0 10px rgba(128, 212, 170, 0.4);
            transform: translateY(-2px);
        }

        button.disconnect {
            background: linear-gradient(45deg, #FFB3B3, #FFCC99);
            color: #333333;
        }

        button.disconnect:hover {
            background: linear-gradient(45deg, #FF9999, #FFDAB9);
            box-shadow: 0 0 10px rgba(255, 153, 153, 0.4);
            transform: translateY(-2px);
        }

        .playlist-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
        }

        .playlist-container label {
            font-weight: 600;
            color: #666666;
        }

        .playlist-container input {
            width: 100%;
            flex: 1;
            padding: 12px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.5);
            color: #333333;
            font-size: 16px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.1);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: rgba(255, 153, 153, 0.2);
            border-color: #FF9999;
            box-shadow: 0 0 10px rgba(255, 153, 153, 0.3);
        }

        .btn i {
            font-size: 16px;
            color: #333333;
        }

        .popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.95);
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: none;
            max-width: 90%;
            text-align: center;
            border: 2px solid #FF9999;
        }

        .popup button {
            width: auto;
            padding: 10px 20px;
            margin: 0 auto;
            display: inline-block;
            background: linear-gradient(45deg, #99E2B7, #A3BFFA);
            color: #333333;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .popup button:hover {
            background: linear-gradient(45deg, #80D4AA, #8C9EFF);
            box-shadow: 0 0 10px rgba(128, 212, 170, 0.6);
            transform: translateY(-2px);
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3);
            display: none;
            z-index: 999;
        }

        @media (max-width: 480px) {
            h2 {
                font-size: 20px;
                color: #FF9999; 
            }

            .form-group input, .playlist-container input {
                font-size: 14px;
                padding: 10px;
                color: #333333;
                background: rgba(255, 255, 255, 0.5);
                border: 1px solid rgba(0, 0, 0, 0.1);
            }

            button.access-btn, button.disconnect {
                font-size: 14px;
                padding: 12px;
                color: #333333;
            }

            button.access-btn {
                background: linear-gradient(45deg, #99E2B7, #A3BFFA);
            }

            button.access-btn:hover {
                background: linear-gradient(45deg, #80D4AA, #8C9EFF);
                box-shadow: 0 0 10px rgba(128, 212, 170, 0.4);
            }

            button.disconnect {
                background: linear-gradient(45deg, #FFB3B3, #FFCC99);
            }

            button.disconnect:hover {
                background: linear-gradient(45deg, #FF9999, #FFDAB9);
                box-shadow: 0 0 10px rgba(255, 153, 153, 0.4);
            }

            .popup {
                width: 90%;
                padding: 15px;
                background: rgba(255, 255, 255, 0.95);
                border: 2px solid #FF9999; 
            }
        }
    </style>
    <script>
        function showPopup() {
            document.getElementById('popup').style.display = 'block';
            document.getElementById('overlay').style.display = 'block';
        }

        function hidePopup(redirect = false) {
            document.getElementById('popup').style.display = 'none';
            document.getElementById('overlay').style.display = 'none';
            if (redirect) {
                window.location.href = window.location.href;
            }
        }

        function confirmDisconnect() {
            showPopup();
            document.getElementById('popup-message').innerText = 'Are you sure you want to Disconnect?';
            document.getElementById('popup-buttons').innerHTML = `
                <button onclick="document.getElementById('disconnect-form').submit()">Yes</button>
                <button onclick="hidePopup()">No</button>
            `;
        }

        function copyToClipboard() {
            var copyText = document.getElementById("playlist_url");
            copyText.select();
            document.execCommand("copy");
            showPopup();
            document.getElementById('popup-message').innerText = 'URL copied: ' + copyText.value;
            document.getElementById('popup-buttons').innerHTML = `
                <button onclick="hidePopup()">OK</button>
            `;
        }

        function redirectToFilter() {
            window.location.href = "filter.php";
        }

        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
       
        document.addEventListener("DOMContentLoaded", function() {            
            let footer = document.createElement("div");
            footer.style.textAlign = "center";
            footer.style.marginTop = "20px";
            footer.style.fontSize = "18px";
            footer.innerHTML = "<strong>Coded with ❤️ by PARAG</strong>";
            
            document.querySelector(".container").appendChild(footer);
        });

        document.addEventListener('DOMContentLoaded', function () {
            <?php if ($show_popup): ?>
                showPopup();
                document.getElementById('popup-message').innerText = '<?= $popup_message ?>';
                document.getElementById('popup-buttons').innerHTML = `
                    <button onclick="hidePopup(true)">OK</button>
                `;
            <?php endif; ?>
        });
    </script>
</head>
<body>
    <div class="container">
        <h2><?php echo $isConnected ? "Device Info" : "Access Xtream Portal"; ?></h2>

        <?php if (!$isConnected): ?>
            <form method="post">
                <div class="form-group">
                    <label>URL:</label>
                    <input type="text" name="url" placeholder="Enter URL" required>
                </div>
                <div class="form-group">
                    <label>User:</label>
                    <input type="text" name="user" placeholder="Enter User Name" required>
                </div>
                <div class="form-group">
                    <label>Password:</label>
                    <input type="text" name="password" placeholder="Enter Password" required>
                </div>                
                <button type="submit" class="access-btn">Access</button>
            </form>
        <?php else: ?>
            <form>
                <?php foreach ($storedData as $key => $value): ?>
                    <div class="form-group">
                        <label><?= ucfirst(str_replace('_', ' ', $key)) ?>:</label>
                        <input type="text" value="<?= $value ?>" readonly>
                    </div>
                <?php endforeach; ?>
            </form>

            <div class="playlist-container">
                <label>Playlist:</label>
                <input type="text" id="playlist_url" value="<?= $playlistUrl ?>" readonly>
                <div class="action-buttons">
                    <button class="btn" onclick="redirectToFilter()">
                        <i class="fas fa-filter"></i>
                    </button>
                    <button class="btn" onclick="copyToClipboard()">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>

            <form method="post" id="disconnect-form">
                <input type="hidden" name="disconnect">
                <button type="button" class="disconnect" onclick="confirmDisconnect()">Disconnect</button>
            </form>
        <?php endif; ?>
        <div class="UserLogout" style="text-align: right; margin-top: 20px;">
            <form action="logout.php" method="POST">
                <button type="submit" class="btn" style="color: black; font-weight: bold; width: auto;">
                    <i class="fas fa-sign-out-alt" style="margin-right: 8px;"></i> Logout
                </button>
            </form>
        </div>
    </div>
    
    <!-- Popup and Overlay -->
    <div id="overlay" class="overlay" onclick="hidePopup()"></div>
    <div id="popup" class="popup <?php echo $popup_type; ?>">
        <p id="popup-message"></p>
        <div id="popup-buttons">
            <button onclick="hidePopup()">OK</button>
        </div>
    </div>
</body>
</html>
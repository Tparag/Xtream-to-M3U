<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$jsonFile = 'data/data.json';
if (!file_exists($jsonFile)) {
    die('Error: data/data.json file not found.');
}

$jsonData = file_get_contents($jsonFile);
$data = json_decode($jsonData, true);

if (json_last_error() !== JSON_ERROR_NONE || !isset($data['url'], $data['user'], $data['password'])) {
    die('Error: Invalid or incomplete data in data/data.json.');
}

$serverURL = $data['url'];
$username = $data['user'];
$password = $data['password'];

$parsedUrl = parse_url($serverURL);
$hostname = isset($parsedUrl['host']) ? $parsedUrl['host'] : 'playlist';
$playlistName = preg_replace('/[^a-zA-Z0-9]/', '', $hostname);
$playlistFile = $playlistName . '.m3u';
$filterFile = 'data/filters/' . $playlistName . '.json';

$currentUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$scriptName = basename($_SERVER['SCRIPT_NAME']);
if (empty($scriptName) || $scriptName == "index.php") {    
    $playlistUrl = rtrim($currentUrl, "/") . "/playlist.php";
} else {    
    $playlistUrl = str_replace($scriptName, "playlist.php", $currentUrl);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $saveDir = 'data/playlist/';
    $filterDir = 'data/filters/';

    if (!is_dir($saveDir)) {
        mkdir($saveDir, 0755, true);
    }
    if (!is_dir($filterDir)) {
        mkdir($filterDir, 0755, true);
    }

    if (isset($_POST['m3uContent'])) {
        $m3uContent = $_POST['m3uContent'];
        $savePath = $saveDir . $playlistFile;

        if (file_put_contents($savePath, $m3uContent) === false) {
            echo json_encode(['success' => false, 'message' => 'Failed to save playlist.']);
            exit;
        }
    }

    if (isset($_POST['selectedCategories'])) {
        $selectedCategories = json_decode($_POST['selectedCategories'], true);
        $filterData = ['selectedCategories' => $selectedCategories];
        $filterPath = $filterDir . $playlistName . '.json';

        if (file_put_contents($filterPath, json_encode($filterData, JSON_PRETTY_PRINT)) === false) {
            echo json_encode(['success' => false, 'message' => 'Failed to save filter categories.']);
            exit;
        }
    }

    echo json_encode(['success' => true, 'message' => "Category filter saved successfully!"]);
    exit;
}

$filterData = [];
if (file_exists($filterFile)) {
    $filterJson = file_get_contents($filterFile);
    $filterData = json_decode($filterJson, true);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xtream Categories Filter</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
            max-height: 85vh;
            width: 90%;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #FF9999;
            margin: 0 0 25px 0;
            text-align: center;
        }

        .checkbox-container {
            max-height: 250px;
            overflow-y: auto;
            padding: 15px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 12px 0;
            padding: 8px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.5);
            transition: background 0.2s;
        }

        .form-group:hover {
            background: rgba(255, 255, 255, 0.7);
        }

        .form-group label {
            flex: 1;
            text-align: left;
            font-weight: 500;
            color: #666666;
            padding-left: 10px;
            cursor: pointer;
        }

        .form-group input[type="checkbox"] {
            transform: scale(1.2);
            cursor: pointer;
            margin-right: 10px;
        }

        button.save-btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            background: linear-gradient(45deg, #99E2B7, #A3BFFA);
            color: #333333;
        }

        button.save-btn:hover {
            background: linear-gradient(45deg, #80D4AA, #8C9EFF);
            box-shadow: 0 0 10px rgba(128, 212, 170, 0.4);
            transform: translateY(-2px);
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

        .popup p {
            margin: 0 0 20px;
            color: #333333;
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

        .search-container {
            margin-bottom: 20px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.5);
            color: #333333;
            font-size: 14px;
            box-sizing: border-box;
        }

        .search-input:focus {
            outline: none;
            border-color: #FF9999;
            box-shadow: 0 0 5px rgba(255, 153, 153, 0.5);
        }

        .search-container::after {
            content: '🔍';
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666666;
        }

        #loadingIndicator {
            font-size: 18px;
            font-weight: bold;
            display: none;
            color: #FF9999;
            margin: 20px 0;
        }

        .playlist-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
        }

        .playlist-container input {
            flex: 1;
            padding: 12px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.5);
            color: #333333;
            font-size: 14px;
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

        @media (max-width: 480px) {
            .container {
                padding: 20px;
                background: rgba(255, 255, 255, 0.9);
                border: 1px solid rgba(0, 0, 0, 0.1);
            }

            h2 {
                font-size: 1.5em;
                color: #FF9999;    
            }

            .form-group {
                flex-direction: column;
                align-items: flex-start;
                padding: 10px;
                background: rgba(255, 255, 255, 0.5);
            }

            .form-group:hover {
                background: rgba(255, 255, 255, 0.7);
            }

            .form-group label {
                padding-left: 0;
                margin-top: 5px;
                color: #666666;
            }

            .popup {
                width: 80%;
                padding: 15px;
                background: rgba(255, 255, 255, 0.95);
                border: 2px solid #FF9999;
            }

            .playlist-container {
                flex-direction: column;
                align-items: stretch;
            }

            .playlist-container input {
                margin-bottom: 10px;
                background: rgba(255, 255, 255, 0.5);
                border: 1px solid rgba(0, 0, 0, 0.1);
                color: #333333;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Xtream Categories Filter</h2>
        <div class="search-container">
            <input type="text" id="searchBox" class="search-input" placeholder="Search categories..." oninput="filterCategories()">
        </div>
        <div id="loadingIndicator">Loading categories...</div>
        <div class="checkbox-container" id="categoryList">
            <div class="form-group">
                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                <label for="selectAll">Select All</label>
            </div>
        </div>
        <button class="save-btn" onclick="saveM3U()">Save</button>
        <div class="playlist-container">
            <input type="text" id="playlist_url" value="<?= htmlspecialchars($playlistUrl) ?>" readonly>
            <button class="btn" onclick="copyToClipboard()">
                <i class="fas fa-copy"></i>
            </button>
        </div>
        <div class="UserLogout" style="text-align: right; margin-top: 20px;">
            <form action="logout.php" method="POST">
                <button type="submit" class="btn" style="color: black; font-weight: bold; width: auto;">
                    <i class="fas fa-sign-out-alt" style="margin-right: 8px;"></i> Logout
                </button>
            </form>
        </div>
        <div class="overlay" id="overlay"></div>
        <div class="popup" id="popup">
            <p id="popupMessage"></p>
            <button onclick="closePopup()">OK</button>
        </div>
    </div>

    <script>
        let categories = [];
        let channels = [];
        let selectedCategories = new Set(<?php echo json_encode($filterData['selectedCategories'] ?? []); ?>);

        const server = <?php echo json_encode($serverURL); ?>;
        const user = <?php echo json_encode($username); ?>;
        const pass = <?php echo json_encode($password); ?>;

        async function fetchCategoriesAndChannels() {
            if (!server || !user || !pass) {
                showPopup("IPTV details are missing in data/data.json.");
                return;
            }

            const baseURL = `${server}/player_api.php?username=${user}&password=${pass}`;
            document.getElementById("loadingIndicator").style.display = "block";
            document.getElementById("categoryList").style.display = "none";

            try {                
                const categoryRes = await fetch(`feeder.php?url=${encodeURIComponent(baseURL + '&action=get_live_categories')}`);
                const categoryText = await categoryRes.text();
                
                if (!categoryRes.ok) {
                    let errorData;
                    try {
                        errorData = JSON.parse(categoryText);
                    } catch {
                        errorData = { error: categoryText || 'Unknown error' };
                    }
                    throw new Error(`HTTP error! Status: ${categoryRes.status} - ${errorData.error || categoryRes.statusText}`);
                }

                categories = JSON.parse(categoryText);
                if (!Array.isArray(categories)) {
                    throw new Error("Invalid category data received from server.");
                }
                displayCategories(categories);
                
                const channelRes = await fetch(`feeder.php?url=${encodeURIComponent(baseURL + '&action=get_live_streams')}`);
                const channelText = await channelRes.text();                

                if (!channelRes.ok) {
                    let errorData;
                    try {
                        errorData = JSON.parse(channelText);
                    } catch {
                        errorData = { error: channelText || 'Unknown error' };
                    }
                    throw new Error(`HTTP error! Status: ${channelRes.status} - ${errorData.error || channelRes.statusText}`);
                }

                channels = JSON.parse(channelText);
                if (!Array.isArray(channels)) {
                    throw new Error("Invalid channel data received from server.");
                }
                showPopup("Categories and channels loaded successfully!");
            } catch (error) {
                console.error("Error fetching data:", error);
                let errorMessage = `Failed to fetch categories or channels. Error: ${error.message}. `;               
                showPopup(errorMessage);
            } finally {
                document.getElementById("loadingIndicator").style.display = "none";
                document.getElementById("categoryList").style.display = "block";
            }
        }

        function displayCategories(filteredCategories) {
            const categoryList = document.getElementById("categoryList");
            const selectAllDiv = categoryList.querySelector('.form-group');
            categoryList.innerHTML = '';
            categoryList.appendChild(selectAllDiv);

            filteredCategories.forEach(cat => {
                const formGroup = document.createElement("div");
                formGroup.className = "form-group";

                const checkbox = document.createElement("input");
                checkbox.type = "checkbox";
                checkbox.value = cat.category_id;
                checkbox.id = `cat_${cat.category_id}`;
                checkbox.checked = selectedCategories.has(cat.category_id);
                checkbox.addEventListener("change", () => {
                    if (checkbox.checked) {
                        selectedCategories.add(cat.category_id);
                    } else {
                        selectedCategories.delete(cat.category_id);
                    }
                    updateSelectAllCheckbox();
                });

                const label = document.createElement("label");
                label.htmlFor = `cat_${cat.category_id}`;
                label.textContent = cat.category_name;

                formGroup.appendChild(checkbox);
                formGroup.appendChild(label);
                categoryList.appendChild(formGroup);
            });

            updateSelectAllCheckbox();
        }

        function filterCategories() {
            const searchValue = document.getElementById("searchBox").value.toLowerCase();
            const filtered = categories.filter(cat => cat.category_name.toLowerCase().includes(searchValue));
            displayCategories(filtered);
        }

        function toggleSelectAll() {
            const selectAllCheckbox = document.getElementById("selectAll");
            const isChecked = selectAllCheckbox.checked;
            const searchValue = document.getElementById("searchBox").value.toLowerCase();
            const filteredCategories = searchValue 
                ? categories.filter(cat => cat.category_name.toLowerCase().includes(searchValue))
                : categories;

            if (isChecked) {
                filteredCategories.forEach(cat => selectedCategories.add(cat.category_id));
            } else {
                filteredCategories.forEach(cat => selectedCategories.delete(cat.category_id));
            }

            document.querySelectorAll('.checkbox-container input[type="checkbox"]:not(#selectAll)').forEach(checkbox => {
                checkbox.checked = isChecked;
            });
        }

        function updateSelectAllCheckbox() {
            const selectAllCheckbox = document.getElementById("selectAll");
            const allCheckboxes = document.querySelectorAll('.checkbox-container input[type="checkbox"]:not(#selectAll)');
            const allChecked = Array.from(allCheckboxes).every(checkbox => checkbox.checked);
            const someChecked = Array.from(allCheckboxes).some(checkbox => checkbox.checked);

            selectAllCheckbox.checked = allChecked;
            selectAllCheckbox.indeterminate = someChecked && !allChecked;
        }

        async function saveM3U() {
            const selected = Array.from(selectedCategories);
            if (!selected.length) {
                showPopup("No categories selected.");
                return;
            }

            const categoryMap = Object.fromEntries(categories.map(cat => [cat.category_id, cat.category_name]));
            const filteredChannels = channels.filter(ch => selected.includes(ch.category_id));

            if (!filteredChannels.length) {
                showPopup("No channels found for the selected categories.");
                return;
            }

            const defaultLogo = "https://i.ibb.co/xK5zSMkD/xtream.png";

            let m3uContent = "#EXTM3U\n";
            filteredChannels.forEach(ch => {
                const categoryName = categoryMap[ch.category_id] || "Unknown";
                const streamURL = `${server}/${user}/${pass}/${ch.stream_id}`;
                
                const logoUrl = ch.stream_icon && ch.stream_icon.trim() !== "" ? ch.stream_icon : defaultLogo;
                m3uContent += `#EXTINF:-1 tvg-id="${ch.stream_id}" tvg-name="${ch.name}" tvg-logo="${logoUrl}" group-title="${categoryName}",${ch.name}\n${streamURL}\n`;
            });

            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `m3uContent=${encodeURIComponent(m3uContent)}&selectedCategories=${encodeURIComponent(JSON.stringify(selected))}`
                });
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                const result = await response.json();
                showPopup(result.message);
            } catch (error) {
                console.error("Error saving playlist:", error);
                showPopup(`Failed to save playlist. Error: ${error.message}.`);
            }
        }

        function copyToClipboard() {
            const playlistUrl = document.getElementById("playlist_url");
            playlistUrl.select();
            try {
                document.execCommand('copy');
                showPopup("Playlist URL copied to clipboard!");
            } catch (err) {
                console.error("Failed to copy: ", err);
                showPopup("Failed to copy URL.");
            }
        }

        function showPopup(message) {
            const popup = document.getElementById("popup");
            const popupMessage = document.getElementById("popupMessage");
            const overlay = document.getElementById("overlay");
            popupMessage.textContent = message;
            popup.style.display = "block";
            overlay.style.display = "block";
        }

        function closePopup() {
            const popup = document.getElementById("popup");
            const overlay = document.getElementById("overlay");
            popup.style.display = "none";
            overlay.style.display = "none";
        }

        window.onload = fetchCategoriesAndChannels;

        document.addEventListener("DOMContentLoaded", function() {            
            let footer = document.createElement("div");
            footer.style.textAlign = "center";
            footer.style.marginTop = "20px";
            footer.style.fontSize = "18px";
            footer.innerHTML = "<strong>Coded with ❤️ by PARAG</strong>";
            
            document.querySelector(".container").appendChild(footer);
        });
    </script>
</body>
</html>
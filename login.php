<?php
$currentUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$scriptName = basename($_SERVER['SCRIPT_NAME']);
$indexUrl = str_replace($scriptName, "", $currentUrl);

session_start();
if (isset($_SESSION['user'])) {
    header("Location: $indexUrl");
    exit();
}

$popup_type = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $valid_username = "parag";
    $valid_password = "parag@123";

    if ($username === $valid_username && $password === $valid_password) {
        $_SESSION['user'] = $username;
        header("Location: $indexUrl");
        exit();
    } else {
        $error = "Invalid username or password!";
        $popup_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xtream Login</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #F7F7FF, #E6E6FA);
            min-height: 100vh;
            margin: 0;
            color: #333333;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            padding: 30px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        h2 {
            color: #FF9999;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #666666;
            margin-bottom: 5px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.5);
            color: #333333;
            font-size: 16px;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: #FF9999;
            box-shadow: 0 0 5px rgba(255, 153, 153, 0.5);
        }

        button {
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
            box-shadow: 0 4px 15px rgba(153, 226, 183, 0.4);
        }

        button:hover {
            background: linear-gradient(45deg, #80D4AA, #8C9EFF);
            box-shadow: 0 6px 20px rgba(128, 212, 170, 0.6);
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
    </style>
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit">Login</button>
        </form>        
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
<script>
    document.addEventListener("DOMContentLoaded", function() {
        let errorMessage = "<?php echo $error; ?>";
        if (errorMessage.trim() !== "") {
            showPopup(errorMessage);
        }
    });

    function showPopup(message) {
        document.getElementById("popup-message").textContent = message;
        document.getElementById("overlay").style.display = "block";
        document.getElementById("popup").style.display = "block";
    }

    function hidePopup() {
        document.getElementById("overlay").style.display = "none";
        document.getElementById("popup").style.display = "none";
    }

    document.addEventListener("DOMContentLoaded", function() {
        let footer = document.createElement("div");
        footer.style.textAlign = "center";
        footer.style.marginTop = "20px";
        footer.style.fontSize = "18px";
        footer.innerHTML = "<strong>Coded with ❤️ by PARAG</strong>";
        
        document.querySelector(".container").appendChild(footer);
    });
</script>
</html>

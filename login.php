<?php
$is_invalid = false;
if ($_SERVER["REQUEST_METHOD"] === "POST"){
    
    $mysqli = require __DIR__ . "/databse.php";

    $sql = sprintf("SELECT * FROM  users WHERE email = '%s'", $mysqli->real_escape_string($_POST["email"]));

    $mysqli->query($sql);

    $result = $mysqli->query($sql);

    $user = $result->fetch_assoc();

    if($user){

        if(password_verify($_POST["password"], $user["password_hash"])){

            session_start();
            $_SESSION["user_id"] = $user["id"];
            header("Location: index.php");
            exit;

        }
    }
    $is_valid = true;
}

?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="initial-scale=1, width=device-width" />
    <link rel="stylesheet" href="./global.css" />
    <link rel="stylesheet" href="./login.css" />
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap"
    />
  </head>
  <body>
    <?php if ($is_invalid): ?>
        <em>Invalid login</em>
    <?php endif; ?>

    <form method="post" id="login" novalidate>
        
    <label for="email">email</label>
    <input type="email" name="email" id="email"
            value="<?= htmlspecialchars($_POST["email"] ?? "") ?>">
    
    <label for="password">Password</label>
    <input type="password" name="password" id="password">
  </form>

    <script>
      var loginText = document.getElementById("loginText");
      if (loginText) {
        loginText.addEventListener("click", function (e) {
          window.location.href = "./active-cases-involving-dr-patient.html";
        });
      }
      </script>
  </body>
</html>
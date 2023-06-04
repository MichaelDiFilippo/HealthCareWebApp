<?php
file_put_contents('debug.txt', print_r($_SERVER, true));
ob_start();  // start output buffering

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$mysqli = include __DIR__ . "/database.php";
session_start();

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve the username and password from the form
    $username = $_POST['username'];
    $password = $_POST['password'];

    // SQL query to check if the username exists and retrieve the hashed password
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        die("Error with DB: " . $mysqli->error);
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        echo "User fetched from DB: " . $row['username'];  // Debug message

        $hashedPassword = $row['password'];

        // Verify the password
        if (password_verify($password, $hashedPassword)) {
            // Password is correct, set the user_id in the session
            $_SESSION['user_id'] = $row['user_id'];

            // Redirect to the logged-in page
            echo "Ready to redirect";  // Debug message
            ob_end_clean();  // Clean the output buffer
            header("Location: patient-information.html");
            exit();
        } else {
            echo "Password verification failed";  // Debug message
        }
    } else {
        echo "User not found";  // Debug message
    }

} else {
    echo "Form was not submitted via POST";  // Debug message
}

?>


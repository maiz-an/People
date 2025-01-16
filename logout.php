<?php
session_start();

if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout Confirmation</title>
    <link rel="stylesheet" href="style.css">
    <script>
        function confirmLogout() {
            var result;
            if (confirm("Are you sure you want to logout?")) {
                result = true;

            }
            if (result == true) {
                window.location.href = 'logout.php?confirm=yes';
            } else {
                window.location.href = document.referrer; // Stay on the same page
            }
        }
    </script>
</head>

<body onload="confirmLogout()">
</body>

</html>
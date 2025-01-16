<?php
// Include database connection
include 'connection.php';


if (isset($_GET['user_id'])) {
    $user_id = intval($_GET['user_id']);
    $query = "DELETE FROM login WHERE id = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);

    if (mysqli_stmt_execute($stmt)) {
        echo "<script>
            alert('User deleted successfully!');
            window.location.href = 'manage_user.php';
        </script>";
    } else {
        echo "<script>
            alert('Error deleting user: " . mysqli_error($connection) . "');
            window.location.href = 'manage_user.php';
        </script>";
    }

    mysqli_stmt_close($stmt);
} else {
    echo "<script>
        alert('Invalid user ID.');
        window.location.href = 'manage_user.php';
    </script>";
}
?>

<?php
// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "demo";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Handle form submission via AJAX
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajax'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $message = $conn->real_escape_string($_POST['message']);

    $sql = "INSERT INTO contact_submissions (name, email, message) VALUES ('$name', '$email', '$message')";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    $conn->close();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - People Platform</title>
    <link rel="stylesheet" href="styles.css"> <!-- Assuming global stylesheet -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');

        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Poppins', sans-serif;
            background-color: #f0f4f8;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        main {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            max-width: 600px;  
            width: 90%;
            text-align: center;
            position: relative;
        }

        h2 {
            color: #4f358e;
            font-size: 1.8rem; 
            margin-bottom: 10px; 
        }

        .center-section {
            display: flex;
            justify-content: space-around;
            margin-bottom: 10px;
        }

        .center-section a {
            width: 40px;
            height: 40px;
            display: inline-block;
            background-size: contain;
            background-repeat: no-repeat;
        }

        .pbutton {
            background-image: url('SiteIcons/phone-icon.png');
        }

        .ebutton {
            background-image: url('SiteIcons/email-icon.png');
        }

        .ibutton {
            background-image: url('SiteIcons/instagram-icon.png');
        }

        .fbutton {
            background-image: url('SiteIcons/facebook-icon.png');
        }

        form {
            margin-top: 15px;  
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .form-group {
            margin-bottom: 15px;
            text-align: left;
            width: 100%;
            max-width: 400px; 
        }

        label {
            display: block;
            font-size: 1rem;  
            color: #555;
            margin-bottom: 5px;
        }

        input[type="text"], input[type="email"], textarea {
            width: 100%;
            padding: 8px;  /* Reduced padding */
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;  
        }

        textarea {
            height: 120px;  
            resize: none;
        }

        .btn {
            background-color: #4f358e;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            font-size: 0.9rem;  
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 15px;
        }

        .btn:hover {
            background-color: #382674;
        }

        .nav-btn {
            background-color: transparent;
            color: #4f358e;
            border: 1px solid #4f358e;
            padding: 6px 18px;
            border-radius: 5px;
            font-size: 0.9rem; 
            cursor: pointer;
            transition: background-color 0.3s ease, color 0.3s ease;
            margin-bottom: 20px;
            position: absolute;
            left: 20px;
            top: 20px;
        }

        .nav-btn:hover {
            background-color: #4f358e;
            color: white;
        }

        .social-links {
            margin-top: 20px;
        }

        .social-links a {
            margin: 0 8px;
            text-decoration: none;
            font-size: 1rem;  
            color: #4f358e;
        }

        .social-links a:hover {
            color: #382674;
        }

        @media (max-width: 600px) {
            main {
                padding: 15px;
                margin: 10px;
            }

            h2 {
                font-size: 1.5rem;
            }

            .center-section {
                flex-direction: column;
                gap: 10px;
            }

            .btn {
                width: 100%;
            }
        }
    </style>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.querySelector("form").addEventListener("submit", function (e) {
                e.preventDefault(); // Prevent the form from submitting the traditional way
                const formData = new FormData(this);
                formData.append('ajax', true);

                fetch('contact_form.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Your message has been sent successfully!');
                        window.location.href = window.location.pathname; // Reload the page to clear the form
                    } else {
                        alert('There was an error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });
        });

        function goBack() {
            window.history.back();
        }
    </script>
</head>

<body>
    <main>
        <!-- Back Button -->
        <button onclick="goBack()" class="btn nav-btn">Back</button>

        <!-- Contact Info -->
        <section id="contact-info">
            <h2>Contact Us</h2>
            <p>We’d love to hear from you. Feel free to reach out to us via phone, email, or through our social media channels.</p>

            <!-- Contact Icons -->
            <section class="center-section">
                <a href="tel:+94753357777" class="pbutton" title="Call Us"></a>
                <a href="mailto:mohamedmaizanmunas@outlook.com" class="ebutton contact-link" title="Email Us"></a>
                <a href="https://www.instagram.com/mr.de11_?igsh=M2NrOWcwcjNicGp0&utm_source=qr" target="_blank" class="ibutton contact-link" title="Instagram"></a>
                <a href="https://www.facebook.com/profile.php?id=61553304986903&mibextid=LQQJ4d" target="_blank" class="fbutton contact-link" title="Facebook"></a>
            </section>
        </section>

        <!-- Contact Form -->
        <section>
            <form method="post">
                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="message">Message:</label>
                    <textarea id="message" name="message" required></textarea>
                </div>
                <input type="submit" value="Send Message" class="btn">
            </form>
        </section>

        <!-- Social Media Links -->
        <div class="social-links">
            <p>Connect with us on:</p>
            <a href="https://www.instagram.com/mr.de11_?igsh=M2NrOWcwcjNicGp0&utm_source=qr" target="_blank"><i class="fa fa-instagram"></i> Instagram</a>
            <a href="https://www.facebook.com/profile.php?id=61553304986903&mibextid=LQQJ4d" target="_blank"><i class="fa fa-facebook"></i> Facebook</a>
            <a href="mailto:mohamedmaizanmunas@outlook.com"><i class="fa fa-envelope"></i> Email</a>
        </div>
    </main>
</body>
</html>

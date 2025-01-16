<?php
session_start();
include 'connection.php'; // Ensure connection to the database is established

$msg = "";
$success_msg = "";
$login_msg = "";  
$reset_msg = "";  


// Login form handling
if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $password = mysqli_real_escape_string($connection, $_POST['password']);

    // Check if the user exists in the login table (for regular users)
    $sql = "SELECT * FROM login WHERE email='$email'";
    $result = mysqli_query($connection, $sql);
    $num = mysqli_num_rows($result);

    if ($num == 1) {
        $row = mysqli_fetch_assoc($result);
               // Check if the user is blocked
               if ($row['status'] == 'blocked') {
                // Redirect to blocked user page
                header("Location: blocked_user.php");
                exit();
            }

        // Verify the password for regular users
        if (password_verify($password, $row['password'])) {
            // Set session variables for successful login
            $_SESSION['id'] = $row['id']; // Store user ID in the session
            $_SESSION['email'] = $row['email'];
            $_SESSION['name'] = $row['name'];
            $_SESSION['gender'] = $row['gender'];
            $_SESSION['user_type'] = $row['user_type'];  // Store user type in session

            // Redirect users based on their user type
            if ($row['user_type'] == 'regular') {
                header("Location: home.php");  // Redirect regular users to home page
                exit();
            } else if ($row['user_type'] == 'organization') {
                header("Location: organizationHome.php");  // Redirect organization users to their dashboard
                exit();
            }
        } else {
            // Incorrect password error
            $login_msg = "Incorrect password. Please try again.";
            $showLoginForm = true; 
        }
    } else {
        // Check in the organization table if user is an organization
        $sql_org = "SELECT * FROM organization WHERE email='$email'";
        $result_org = mysqli_query($connection, $sql_org);
        $num_org = mysqli_num_rows($result_org);

        if ($num_org == 1) {
            $row_org = mysqli_fetch_assoc($result_org);

            // Verify the password for the organization account
            if (password_verify($password, $row_org['org_password'])) {

                // Check if the organization's status is allowed
                if ($row_org['status'] == 'allowed') {
                    // Set session variables for allowed organization users
                    $_SESSION['id'] = $row_org['org_id']; 
                    $_SESSION['email'] = $row_org['email'];
                    $_SESSION['name'] = $row_org['org_name'];
                    $_SESSION['user_type'] = 'organization';  // Set user type as organization

                    // Redirect to organization dashboard
                    header("Location: organizationHome.php");
                    exit();
                } elseif ($row_org['status'] == 'pending') {
                    // Redirect to pending access page if the organization is not yet approved
                    header("Location: pending_access.html");
                    exit();
                } elseif ($row_org['status'] == 'blocked') {
                    // Redirect to a blocked page or show an error message
                    echo "<script>alert('Your account has been blocked. Please contact support.');</script>";
                    exit();
                }
            } } else {
                $login_msg = "Incorrect password for organization account.";
                $showLoginForm = true; 
            } // Close the organization check block
            
            // Check in the admin table
            $sql_admin = "SELECT * FROM admin WHERE email='$email'";
            $result_admin = mysqli_query($connection, $sql_admin);
            $num_admin = mysqli_num_rows($result_admin);
            
            if ($num_admin == 1) {
                $row_admin = mysqli_fetch_assoc($result_admin);
            
                // Verify the password for the admin account
                if (password_verify($password, $row_admin['password'])) {
                    // Set session variables for the admin
                    $_SESSION['id'] = $row_admin['Aid'];
                    $_SESSION['email'] = $row_admin['email'];
                    $_SESSION['name'] = $row_admin['name'];
                    $_SESSION['user_type'] = 'admin'; // Add admin user type
            
                    // Redirect to admin dashboard
                    header("Location: admin.php");
                    exit();
                } else {
                    $login_msg = "Incorrect password for admin account.";
                    $showLoginForm = true;
                }
            } else {
                // Account not found in both tables
                $login_msg = "Account does not exist.";
                $showLoginForm = true;
            } // Close the outermost else block
            
    }
}



if (isset($_POST['signup'])) {
    $username = mysqli_real_escape_string($connection, $_POST['name']);
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $password = mysqli_real_escape_string($connection, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($connection, $_POST['confirmpassword']); // Get confirm password
    $gender = mysqli_real_escape_string($connection, $_POST['gender']);

    $msg = ""; // Initialize the message

    if (!empty($username) && !empty($email) && !empty($password) && !empty($confirm_password) && !empty($gender)) {
        // Check if password matches confirm password
        if ($password !== $confirm_password) {
            $msg = "Passwords do not match. Please try again.";
        } else {
            // Step-by-step password validation
            if (strlen($password) < 8) {
                $msg = "Password must be at least 8 characters long.";
            } elseif (!preg_match('/[A-Z]/', $password)) {
                $msg = "Password must contain at least one uppercase letter.";
            } elseif (!preg_match('/[a-z]/', $password)) {
                $msg = "Password must contain at least one lowercase letter.";
            } elseif (!preg_match('/[0-9]/', $password)) {
                $msg = "Password must contain at least one number.";
            } elseif (!preg_match('/[\W]/', $password)) {
                $msg = "Password must contain at least one special character (e.g., !@#$%^&*).";
            } else {
                // Check if the account already exists in the login table
                $sql = "SELECT * FROM login WHERE email='$email'";
                $result = mysqli_query($connection, $sql);
                $num = mysqli_num_rows($result);

                // Check if the email exists in the admin table
                $sql_admin = "SELECT * FROM admin WHERE email='$email'";
                $result_admin = mysqli_query($connection, $sql_admin);
                $num_admin = mysqli_num_rows($result_admin);

                if ($num == 1 || $num_admin == 1) {
                    // Account already exists in either table
                    $msg = "Account with this email already exists.";
                } else {
                    // Hash the password before storing it
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // Path to the default profile picture
                    $default_image_path = 'SiteIcons/user.png'; // Ensure this file exists
                    $profile_pic = file_get_contents($default_image_path); // Read the image content

                    // Prepare the query to insert the user with the default profile picture
                    $query = "INSERT INTO login (name, email, password, gender, profile_pic) VALUES (?, ?, ?, ?, ?)";
                    $stmt = $connection->prepare($query);
                    if (!$stmt) {
                        die("Error preparing statement: " . $connection->error);
                    }

                    // Bind parameters (5th parameter for profile_pic)
                    $stmt->bind_param('sssss', $username, $email, $hashed_password, $gender, $profile_pic);

                    // Execute the statement
                    if ($stmt->execute()) {
                        // Account creation successful
                        $success_msg = "Account successfully created. Please log in.";
                    } else {
                        // Handle database errors
                        $msg = "Error saving the account. Please try again.";
                    }
                }
            }
        }
    } else {
        // Handle form validation error
        $msg = "Please fill in all required fields.";
    }
}




// Reset password 
if (isset($_POST['reset_password'])) {
    $email = mysqli_real_escape_string($connection, $_POST['usernameOrEmail']);
    $newPassword = mysqli_real_escape_string($connection, $_POST['newPassword']);
    $confirmNewPassword = mysqli_real_escape_string($connection, $_POST['confirmNewPassword']);

    if ($newPassword !== $confirmNewPassword) {
        $reset_msg  = "Passwords do not match. Please try again.";
        $showResetForm = true; // Ensure form stays open
    } else {
        $sql = "SELECT * FROM login WHERE email='$email'";
        $result = mysqli_query($connection, $sql);
        if (mysqli_num_rows($result) == 1) {
            $hashed_password = password_hash($newPassword, PASSWORD_DEFAULT);
            $sql_update = "UPDATE login SET password='$hashed_password' WHERE email='$email'";
            if (mysqli_query($connection, $sql_update)) {
                $success_msg = "Your password has been successfully reset.";
                $showResetForm = false; // Close the form
            } else {
                $reset_msg  = "Error updating the password.";
                $showResetForm = true; // Keep the form open
            }
        } else {
            $reset_msg  = "Account with this email does not exist.";
            $showResetForm = true; // Keep the form open
        }
    }
}



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>People: Community Sharing Platform</title>
    <link rel="icon" type="image/png" href="people.png">
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="login-btn.css">
    <link rel="stylesheet" href="floating_contact_button.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .img-icon{
            width: 80px;
            height: 80px;
        }
        /* Overlay */
        .overlay {
            visibility: hidden; 
            position: fixed;
            top: 0;  
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8); 
            z-index: 9999;
            display: flex;  
            justify-content: center;  
            align-items: center;  
            opacity: 0;
            transition: opacity 0.5s ease, visibility 0.5s ease; 
        }

        .overlay.active {
            visibility: visible; 
            opacity: 1; 
        }

        /* Form Container */
        .form-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 400px;
            width: 90%;
            position: fixed;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            text-align: center;
            transform: translateY(-50px); 
            opacity: 0; 
            transition: transform 0.5s ease, opacity 0.5s ease;
        }

        .form-container.active {
            transform: translateY(0); 
            opacity: 1; 
        }

        /* Close Button */
        .close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 2rem;
            color: #333;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close-btn:hover {
            color: #ff009a; 
        }

        /* Common styles for login and signup forms */
        .form-heading {
            font-size: 1.8rem;
            margin-bottom: 20px;
            color: #06C167;
        }

        .input input,
        .password input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .btn button {
            background-color: #4f358e;
            color: white;
            border: none;
            margin-bottom: 10px;
            margin-top: 10px;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn button:hover {
            background-color: #4f358e;
            transform: scale(1.05);
        }

        .btn-lg button {
            background-color: #4f358e;
            color: white;
            border: none;
            margin-bottom: 10px;
            margin-top: 20px;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-lg button:hover {
            background-color: #4f358e;
            transform: scale(1.05);
        }

        .signin-up a {
            color: #ff009a; 
            text-decoration: none;
        }

        .signin-up a:hover {
            text-decoration: underline;
        }
        .signin-up p {
            size: 5px;
        }

        .password {
            position: relative;
        }

        .fpass {
            position: absolute;
            margin-top: 40px;
            left: 5px; /* Align to the left side */
            top: 50%;
            transform: translateY(-50%); /* Vertically center the text */
            font-size: 0.8rem; /* Adjust the font size as needed */
            color: #ff009a; /* Customize the color */
            text-decoration: none;
        }

        .fpass:hover {
            text-decoration: underline;
        }

        .showHidePw {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }

        /* Hide the form when switching */
        .form-container {
            display: none;
        }

        .form-container.active {
            display: block;
        }
        .radio {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .gender-label {
            margin-right: 10px;
            font-weight: bold;
        }

        .gender-options {
            display: flex;
            gap: 5px;
        }

        .gender-options input[type="radio"] {
            margin-right: 5px;
            margin-left: 15px;
        }

        .confirmpassword input{
            margin-bottom: 10px;
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 15px;
            font-size: 14px;
            text-decoration: none;
            text-align: left;
        }
        .form-group a{
            text-decoration: none ;
        }
        .checkbox-custom {
            margin-top: 5px;
            width: 15px;
            height: 15px;
        }

        @media (max-width: 435px) {
            .fpass {
                margin-top: 32px;
                font-size: 0.69rem;
            }
        }

    </style>
</head>
<body>
    <header class="header">
        <h1 class="logo">People</h1>
        <nav class="nav" id="nav">
            <a href="#home">Home</a>
            <a href="#share">Share</a>
            <a href="#community">Community</a>
            <a href="#donate">Donate</a>
            
        </nav>
    </header>
    
    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="container">
            <img src="SiteIcons/logo.png" class="logo-mob" alt="logo">
            <h1 class="hero-title">Join on <span>People</span>  </h1>
            <h3>then you can <span class="multiple-text"></span> </h3>
            <p class="hero-text">
                A platform where community and compassion meet. "People" empowers individuals to share surplus resources, donate to worthy causes, and extend a helping hand to those in need. Together, we can build a stronger, more sustainable community by reducing waste, redistributing excess food, and offering essential goods to those less fortunate.

Our platform is dedicated to supporting the homeless and marginalized groups by enabling others to post requests on their behalf for food, shelter, and clothing. "People" also simplifies the process of offering or finding land for sale or rent, bringing transparency and accessibility to land listings within the community. Whether you're a donor, an advocate, or someone in need, "People" creates a seamless bridge between you and your community.

Through structured donation campaigns, individuals and organizations can come together to raise funds and offer aid for critical causes, from disaster relief to supporting local non-profits. Whether you're looking to give back, share resources, or receive assistance, "People" connects those who want to make a difference with those who need it most. Join us today in fostering a culture of generosity, compassion, and shared responsibility to create lasting positive change for both individuals and neighborhoods alike.


            </p>
            <a href="#"  class="hero-button" >Get Started</a>
        </div>
    </section>

    <!-- Share Section -->
    <section id="share" class="section">
        <div class="container">
            <h2 class="section-title"> <span>Share With The Community</span></h2>
            <div class="cards">
                <div class="card">
                    <img src="SiteIcons/sharing.png" class="img-icon" alt="icons" />
                    <h3 class="card-title">Food Sharing</h3>
                    <p class="card-text">Post surplus food items for others in your community. Let's reduce waste and help those in need.</p>
                </div>
                <div class="card">
                    <img src="SiteIcons/beggar.png" class="img-icon" alt="icons" />
                    <h3 class="card-title">Homeless Support</h3>
                    <p class="card-text">Support homeless individuals by posting requests on their behalf for food, clothing, or shelter.</p>
                </div>
                <div class="card">
                    <img src="SiteIcons/for-sale.png" class="img-icon" alt="icons" />
                    <h3 class="card-title">Land Listings</h3>
                    <p class="card-text">Post land sales or rentals and manage listings with ease.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Community Section -->
    <section id="community" class="section section-dark">
        <div class="container">
            <h2 class="section-comunity"><span>Join The Community</span></h2>
            <!-- <p class="section-text">Participate in discussions, raise awareness, and connect with others to make a difference.</p> -->
            <div class="cards">
                <div class="card-ws">
                    <img src="SiteIcons/meeting.png" class="img-icon" alt="icons" />
                    <h3 class="card-title">Community Discussions</h3>
                    <p class="card-text">Join discussions to share knowledge, raise awareness, and collaborate with others on community goals.</p>
                </div>
                <div class="card-ws">
                    <img src="SiteIcons/calendar.png" class="img-icon" alt="icons" />
                    <h3 class="card-title">Events & Meetups</h3>
                    <p class="card-text">Stay informed about local events focused on donations, homeless support, and helping those in need.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Donate Section -->
    <section id="donate" class="section">
        <div class="container">
            <h2 class="section-title"><span>Donate For a Cause</span></h2>
            <div class="cards">
                <div class="card">
                    <img src="SiteIcons/donation.png" class="img-icon" alt="icons" />
                    <h3 class="card-title">Donation Requests</h3>
                    <p class="card-text">Create donation requests for food, clothing, or support for a cause you care about.</p>
                </div>
                <div class="card">
                    <img src="SiteIcons/gift.png" class="img-icon" alt="icons" />
                    <h3 class="card-title">Offer Donations</h3>
                    <p class="card-text">Help your community by donating goods, services, or funds to ongoing initiatives.</p>
                </div>
            </div>
        </div>
    </section>

<!--  Footer Section -->
<footer class="footer">
    <div class="footer-container">
        <div class="footer-top">
            <div class="footer-brand">
                <h2>People</h2>
                <p>Connecting people, reducing waste, and supporting communities.</p>
            </div>
            <div class="footer-menu">
                <ul class="menu-list">
                    <li><a href="#">About</a></li>
                    <li><a href="#">Careers</a></li>
                    <li><a href="#">Contact</a></li>
                </ul>
                <ul class="menu-list">
                    <li><a href="#">Help Center</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                </ul>
                <ul class="menu-list">
                    <li><a href="#">Food Sharing</a></li>
                    <li><a href="#">Homeless Support</a></li>
                    <li><a href="#">Donate</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
        <ul class="list">
            <li><a href="#">About </a></li>
            <li><a href="#">Contact </a></li>
            <li><a href="#">Careers</a></li>
        </ul>
        <ul class="list">
            <li><a href="#">Help Center </a></li>
            <li><a href="#">Privacy Policy</a></li>
            <li><a href="#">Privacy Policy</a></li>
        </ul>
        
            <div class="social-icons">
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fa fa-whatsapp" style="font-size:28px;" ></i></a>
            </div>
            <p>&copy; 2024 People. All Rights Reserved.</p>
        </div>
    </div>
</footer>

<!-- Floating login Button -->
<div class="floating-lg">
    <a href="#" class="lg-btn" id="loginBtn">Login</a>
</div>

<!-- Floating Contact Button -->
<div class="floating-contact">
        <a href="contact_form.php" class="contact-btn">
            <img src="SiteIcons/customer-service.png" alt="Contact Us">
        </a>
</div>

<!-- Overlay -->
<div id="overlay" class="overlay <?php echo ($msg || $success_msg) ? 'active' : ''; ?>">
    <!-- Login Form -->
    <div id="loginForm" class="form-container <?php echo ($success_msg || $msg) ? 'active' : ''; ?>">
        <span class="close-btn" id="closeOverlay">&times;</span>
        <form action="" method="post">
            <h2 class="form-heading">Sign in to <span>People</span></h2>
            <?php if (!empty($success_msg)): ?>
                <p class="success-message" style="color: green;"><?php echo $success_msg; ?></p>
            <?php endif; ?>

            <?php if (!empty($login_msg)): ?>
                <p class="error-message" style="color: red;"><?php echo $login_msg; ?></p>
            <?php endif; ?>
            <div class="input">
        <input 
            type="email" 
            name="email" 
            placeholder="Email" 
            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
            required 
        />
    </div>
            <div class="password">
                <input type="password" placeholder="Password" name="password" id="password" required />
                <i class="uil uil-eye-slash showHidePw"></i>
                <a href="#" id="forgetpassword" class="fpass">Forget password?</a>
            </div>
            <div class="btn-lg">
                <button type="submit" name="login">Sign In</button>
            </div>
            <div class="signin-up">
                <p>Don't have an account? <a href="#" id="showSignup">Register</a></p>
                <P>Register an organization <a href="signup_organization.php" target="_blank" id="showOrganization">Here!</a></P>
            </div>

            
        </form>
    </div>

    <!-- Sign-Up Form -->
    <div id="signupForm" class="form-container <?php echo (!empty($msg) && isset($_POST['signup'])) ? 'active' : ''; ?>">
        <span class="close-btn" id="closeOverlay">&times;</span>
        <form action="" method="post">
            <h2 class="form-heading">Create your <span>People</span> </h2>
            <!-- Display error message if account creation fails -->
            <?php if (!empty($msg) && isset($_POST['signup'])): ?>
                <p style="color: red;"><?php echo $msg; ?></p>
            <?php endif; ?>
            <div class="input">
        <input 
            type="text" 
            name="name" 
            placeholder="Name" 
            value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
            required 
        />
    </div>

    <div class="input">
        <input 
            type="email" 
            name="email" 
            placeholder="Email" 
            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
            required 
        />
    </div>
    <div class="password">
        <input type="password" name="password" placeholder="Password" required />
    </div>

    <div class="password">
        <input type="password" name="confirmpassword" placeholder="Confirm Password" required />
    </div>

    <div class="radio">
        <label for="gender" class="gender-label">Gender: </label>
        <div class="gender-options">
            <input 
                type="radio" 
                name="gender" 
                value="male" 
                id="male" 
                <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'male') ? 'checked' : ''; ?> 
                required 
            />
            <label for="male">Male</label>
            
            <input 
                type="radio" 
                name="gender" 
                value="female" 
                id="female" 
                <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'female') ? 'checked' : ''; ?> 
                required 
            />
            <label for="female">Female</label>
        </div>
    </div>



            <!-- Agreement -->
            <div class="form-group">
                <input type="checkbox" id="terms" name="terms" required class="checkbox-custom">
                <label for="terms">I agree to the <a href="#">Terms and Conditions</a> and <a href="#">Privacy Policy</a>.</label>
            </div>
            <div class="btn">
                <button type="submit" name="signup">Continue</button>
            </div>
            <div class="signin-up">
                <p>Already have an account? <a href="#" id="showLogin">Sign In</a></p>
            </div>
        </form>
    </div>
    
<!-- Forgot Password Form -->
<div id="forgetPasswordForm" class="form-container <?php echo (!empty($reset_msg) || isset($showResetForm) && $showResetForm) ? 'active' : ''; ?>">
    <span class="close-btn" id="closeOverlay">&times;</span>
    <form action="" method="post" onsubmit="return validatePasswords()">
        <h2 class="form-heading">Reset Your <span>Password</span></h2>

        <!-- Display error message if reset password fails -->
        <?php if (!empty($reset_msg)): ?>
            <p style="color: red;"><?php echo $reset_msg; ?></p>
        <?php endif; ?>

        <!-- Display success message after password reset -->
        <?php if (!empty($success_msg)): ?>
            <p style="color: green;"><?php echo $success_msg; ?></p>
        <?php endif; ?>

        <div class="input">
            <input type="email" name="usernameOrEmail" placeholder="Enter your email" required />
        </div>
        <div class="password">
            <input type="password" name="newPassword" placeholder="New Password" id="newPassword" required />
        </div>
        <div class="password">
            <input type="password" name="confirmNewPassword" placeholder="Confirm New Password" id="confirmNewPassword" required />
        </div>
        <div class="btn">
            <button type="submit" name="reset_password">Reset Password</button>
        </div>
        <div class="signin-up">
            <p>Remembered password? <a href="#" id="backToLogin">Back to Login</a></p>
        </div>
    </form>
</div>

</div>



</div>


<script>
    document.addEventListener('DOMContentLoaded', function() {
    const overlay = document.getElementById('overlay');
    const loginBtn = document.getElementById('loginBtn');
    const closeOverlay = document.querySelectorAll('#closeOverlay');
    const loginForm = document.getElementById('loginForm');
    const signupForm = document.getElementById('signupForm');
    const showSignup = document.getElementById('showSignup');
    const showLogin = document.getElementById('showLogin');
    const forgetPasswordLink = document.getElementById('forgetpassword');
    const forgetPasswordForm = document.getElementById('forgetPasswordForm');
    const backToLoginLink = document.getElementById('backToLogin');
    const showLoginForm = <?php echo isset($showLoginForm) && $showLoginForm ? 'true' : 'false'; ?>;

    // Function to reset forms
    function closeForms() {
        overlay.classList.remove('active');
        loginForm.classList.remove('active');
        signupForm.classList.remove('active');
        forgetPasswordForm.classList.remove('active');
    }

    // Show login form
    loginBtn?.addEventListener('click', function(event) {
        event.preventDefault();
        overlay.classList.add('active');
        loginForm.classList.add('active');
        signupForm.classList.remove('active');
    });

    // Show sign-up form from login form
    showSignup?.addEventListener('click', function(event) {
        event.preventDefault();
        loginForm.classList.remove('active');
        signupForm.classList.add('active');
    });

    // Show login form from sign-up form
    showLogin?.addEventListener('click', function(event) {
        event.preventDefault();
        signupForm.classList.remove('active');
        loginForm.classList.add('active');
    });

    // Close overlay on close button click
    closeOverlay.forEach(btn => {
        btn.addEventListener('click', function() {
            closeForms(); // Close all forms
        });
    });

    // Close overlay if clicked outside of forms
    window.addEventListener('click', function(event) {
        if (event.target === overlay) {
            closeForms(); // Close all forms
        }
    });

    // Open the reset password form
    forgetPasswordLink?.addEventListener('click', function(event) {
        event.preventDefault();
        forgetPasswordForm.classList.add('active');
        loginForm.classList.remove('active');
    });

    // Return to login form from reset password form
    backToLoginLink?.addEventListener('click', function(event) {
        event.preventDefault();
        forgetPasswordForm.classList.remove('active'); // Hide reset password form
        loginForm.classList.add('active'); // Show login form again
    });

    // If PHP shows the reset password form (when reset form has errors)
    const showResetForm = <?php echo isset($showResetForm) && $showResetForm ? 'true' : 'false'; ?>;
    if (showResetForm) {
        forgetPasswordForm.classList.add('active');
        loginForm.classList.remove('active');
        overlay.classList.add('active');
    }

    if (showLoginForm) {
        loginForm.classList.add('active');  
        overlay.classList.add('active');   
    }
});

// Validate passwords in reset form
function validatePasswords() {
    const newPassword = document.getElementById("newPassword").value;
    const confirmNewPassword = document.getElementById("confirmNewPassword").value;

    if (newPassword !== confirmNewPassword) {
        alert("Passwords do not match. Please try again.");
        return false; 
    }
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    // Check for the success parameter in the URL
    const urlParams = new URLSearchParams(window.location.search);
    const success = urlParams.get('success');

    if (success) {
        // Display success message if registration was successful
        const loginForm = document.getElementById('loginForm');
        const successMessage = document.createElement('p');
        successMessage.textContent = 'Organization registered successfully! Please log in.';
        successMessage.style.color = 'green';
        loginForm.prepend(successMessage);

        // Auto-open the login form
        const overlay = document.getElementById('overlay');
        overlay.classList.add('active');
        loginForm.classList.add('active');
    }
});



</script>

    <script src="https://cdn.jsdelivr.net/npm/typed.js@2.0.12"></script>
    <script src="script.js"></script>
</body>
</html>

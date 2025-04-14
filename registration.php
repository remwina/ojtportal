<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'DBCONNECT.php';

if (isset($_POST['register'])) {
    $sr_code = mysqli_real_escape_string($conn, $_POST['sr_code']);
    $firstname = mysqli_real_escape_string($conn, $_POST['firstname']);
    $lastname = mysqli_real_escape_string($conn, $_POST['lastname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);
    $course = mysqli_real_escape_string($conn, $_POST['course']);
    $section = mysqli_real_escape_string($conn, $_POST['section']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    
    $errors = array();

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        array_push($errors, "Invalid email format");
    }
    
    if (strlen($password) < 8) {
        array_push($errors, "Password must be at least 8 characters long");
    }
    
    if (empty($sr_code)) { array_push($errors, "SR Code is required"); }
    if (empty($firstname)) { array_push($errors, "First Name is required"); }
    if (empty($lastname)) { array_push($errors, "Last Name is required"); }
    if (empty($email)) { array_push($errors, "Email is required"); }
    if (empty($password)) { array_push($errors, "Password is required"); }
    if ($password != $confirm_password) { array_push($errors, "Passwords do not match"); }
    if (empty($course)) { array_push($errors, "Course is required"); }
    if (empty($section)) { array_push($errors, "Section is required"); }
    if (empty($department)) { array_push($errors, "Department is required"); }
    
    $email_check_query = "SELECT * FROM students WHERE email='$email' LIMIT 1";
    $result = mysqli_query($conn, $email_check_query);
    $user = mysqli_fetch_assoc($result);
    
    if ($user) {
        if ($user['email'] === $email) {
            array_push($errors, "Email already exists");
        }
    }
    
    if (count($errors) == 0) {
        $password = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO students (sr_code, firstname, lastname, email, password, course, section, department) 
                  VALUES(?, ?, ?, ?, ?, ?, ?, ?)";
                  
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssssssss", $sr_code, $firstname, $lastname, $email, $password, $course, $section, $department);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Registration successful! You can now login.";
            header('location: login.php');
            exit();
        } else {
            array_push($errors, "Registration failed: " . mysqli_error($conn));
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - BatState-U Lipa OJT Portal</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container registration-container">
        <div class="header text-center">
            <div class="logo-container">
                <img src="BatStateU-NEU-Logo.png" alt="BatState-U Logo" class="logo">
            </div>
            <h2>BatState-U Lipa Campus</h2>
            <div class="title-divider"></div>
            <h3>OJT Student Registration Portal</h3>
        </div>

        <form method="POST" action="registration.php">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <p class="mb-0"><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                        echo htmlspecialchars($_SESSION['success']);
                        unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="sr_code">SR Code</label>
                        <input type="text" class="form-control" id="sr_code" name="sr_code" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="firstname">First Name</label>
                        <input type="text" class="form-control" id="firstname" name="firstname" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="lastname">Last Name</label>
                        <input type="text" class="form-control" id="lastname" name="lastname" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="email">Email (@g.batstate-u.edu.ph)</label>
                        <input type="email" class="form-control" id="email" name="email" pattern=".+@g\.batstate-u\.edu\.ph" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="department">Department</label>
                        <select class="form-control" id="department" name="department" required>
                            <option value="">Select Department</option>
                            <option value="CICS">College of Informatics and Computing Sciences</option>
                            <option value="CET">College of Engineering Technology</option>
                            <option value="CAS">College of Arts and Sciences</option>
                            <option value="CTE">College of Teacher Education</option>
                            <option value="CABE">College of Accountancy, Business and Economics</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="course">Course</label>
                        <input type="text" class="form-control" id="course" name="course" 
                               placeholder="e.g., BSIT" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="section">Section</label>
                        <input type="text" class="form-control" id="section" name="section" placeholder="e.g., 4201" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
            </div>

            <div class="text-center mt-3">
                <button type="submit" name="register" class="btn btn-primary">Register</button>
                <p class="mt-2 mb-0" style="font-size: 0.9rem;">Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

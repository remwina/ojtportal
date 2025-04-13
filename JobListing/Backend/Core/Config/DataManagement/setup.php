<?php
header('Content-Type: text/html');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to check if dependencies are installed
function areDependenciesInstalled() {
    return file_exists(__DIR__ . '/../../../../../vendor/autoload.php');
}

// Check current step
$step = isset($_GET['step']) ? $_GET['step'] : 'check_dependencies';
$message = '';
$error = '';

// Handle steps
switch($step) {
    case 'run_migration':
        if (areDependenciesInstalled()) {
            try {
                ob_start();
                require_once __DIR__ . '/migrate.php';
                ob_end_clean();
                $message = "Database setup completed successfully!";
                $step = 'complete';
            } catch (Exception $e) {
                $error = $e->getMessage();
                $step = 'error';
            }
        } else {
            $step = 'check_dependencies';
        }
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JobListing System Setup</title>
    <link rel="stylesheet" href="/Finals_But_Its_ADBMS/JobListing/Assets/Styles/style.css">
    <style>
        .setup-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 800px;
            margin: 1.25rem;
            border: 1px solid var(--gray-100);
            position: relative;
            z-index: 10;
        }
        .step {
            display: none;
        }
        .step.active {
            display: block;
            animation: fadeIn 0.3s ease-out;
        }
        .step-indicators {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }
        .step-indicators::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--gray-200);
            z-index: 0;
        }
        .step-indicator {
            position: relative;
            z-index: 1;
            text-align: center;
            flex: 1;
        }
        .step-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
            color: var(--gray-600);
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .step-indicator.active .step-circle {
            background: var(--primary-red);
            color: var(--white);
        }
        .step-indicator.completed .step-circle {
            background: var(--dark-red);
            color: var(--white);
        }
        .step-label {
            color: var(--gray-600);
            font-size: 0.875rem;
            font-weight: 500;
        }
        .terminal {
            background: var(--gray-700);
            color: var(--white);
            padding: 1rem;
            border-radius: 12px;
            font-family: monospace;
            margin: 1rem 0;
            position: relative;
        }
        .copy-btn {
            position: absolute;
            right: 0.5rem;
            top: 0.5rem;
            background: rgba(255,255,255,0.1);
            border: none;
            color: var(--white);
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        .copy-btn:hover {
            background: rgba(255,255,255,0.2);
        }
        .setup-btn {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: auto;
            padding: 0.875rem 2rem;
            background: var(--primary-red);
            color: var(--white);
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        .setup-btn:hover {
            background: var(--dark-red);
        }
        .card {
            background: var(--white);
            border-radius: 12px;
            border: 1px solid var(--gray-200);
            margin-bottom: 1.5rem;
        }
        .card-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            background: var(--gray-50);
            border-radius: 12px 12px 0 0;
        }
        .card-body {
            padding: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="blob"></div>
    <div class="setup-container">
        <h1>JobListing System Setup</h1>
        <p class="subtitle">Follow the steps below to set up your system</p>
        
        <div class="step-indicators">
            <div class="step-indicator <?php echo in_array($step, ['check_dependencies', 'run_migration', 'complete']) ? 'active' : ''; ?>">
                <div class="step-circle">1</div>
                <div class="step-label">Dependencies</div>
            </div>
            <div class="step-indicator <?php echo in_array($step, ['run_migration', 'complete']) ? 'active' : ''; ?>">
                <div class="step-circle">2</div>
                <div class="step-label">Database</div>
            </div>
            <div class="step-indicator <?php echo $step === 'complete' ? 'active' : ''; ?>">
                <div class="step-circle">3</div>
                <div class="step-label">Complete</div>
            </div>
        </div>

        <?php if ($error): ?>
        <div class="error-message">
            <h4>Error Occurred</h4>
            <p><?php echo htmlspecialchars($error); ?></p>
            <hr>
            <p>Please ensure:</p>
            <ul>
                <li>XAMPP is running (Apache and MySQL)</li>
                <li>MySQL password in DB_Operations.php matches your XAMPP settings</li>
            </ul>
            <a href="setup.php" class="setup-btn">Try Again</a>
        </div>
        <?php endif; ?>

        <!-- Step 1: Dependencies -->
        <div class="step <?php echo $step === 'check_dependencies' ? 'active' : ''; ?>">
            <h3>Step 1: Dependencies Installation</h3>
            <?php if (!areDependenciesInstalled()): ?>
                <div class="subtitle">
                    Dependencies need to be installed. Please follow these steps:
                </div>
                <ol class="mb-4">
                    <li>Open a command prompt/terminal</li>
                    <li>
                        Navigate to the project directory:
                        <div class="terminal">
                            cd <?php echo htmlspecialchars(realpath(__DIR__ . '/../../../../..')); ?>
                            <button class="copy-btn" onclick="copyToClipboard(this)" data-text="cd <?php echo realpath(__DIR__ . '/../../../../..'); ?>">Copy</button>
                        </div>
                    </li>
                    <li>
                        Run this command:
                        <div class="terminal">
                            composer install
                            <button class="copy-btn" onclick="copyToClipboard(this)" data-text="composer install">Copy</button>
                        </div>
                    </li>
                </ol>
                <button class="setup-btn" onclick="checkDependencies()">I've Installed Dependencies</button>
            <?php else: ?>
                <div class="subtitle">
                    ✓ Dependencies are installed correctly!
                </div>
                <a href="?step=run_migration" class="setup-btn">Continue to Database Setup</a>
            <?php endif; ?>
        </div>

        <!-- Step 2: Database Setup -->
        <div class="step <?php echo $step === 'run_migration' ? 'active' : ''; ?>">
            <h3>Step 2: Database Setup</h3>
            <div class="subtitle">
                We'll now set up your database and create an admin account.
                Please ensure XAMPP (Apache and MySQL) is running before proceeding.
            </div>
            <div class="text-center">
                <a href="?step=run_migration&confirm=1" class="setup-btn">Set Up Database</a>
            </div>
        </div>

        <!-- Step 3: Complete -->
        <div class="step <?php echo $step === 'complete' ? 'active' : ''; ?>">
            <h3>Setup Complete! 🎉</h3>
            <div class="subtitle">
                Your JobListing system has been set up successfully!
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Admin Login Credentials</h5>
                </div>
                <div class="card-body">
                    <p><strong>Email:</strong> admin@admin.com</p>
                    <p><strong>Password:</strong> admin123</p>
                    <p><strong>SR Code:</strong> 21-00001</p>
                </div>
            </div>

            <div class="subtitle">
                <h5>Next Steps:</h5>
                <ol>
                    <li>Go to the login page</li>
                    <li>Log in using the admin credentials above</li>
                    <li>Change your password after first login</li>
                </ol>
            </div>

            <div class="text-center">
                <a href="/Finals_But_Its_ADBMS/JobListing/Frontend/login.html" class="setup-btn">Go to Login Page</a>
            </div>
        </div>
    </div>

    <script>
        function copyToClipboard(btn) {
            const text = btn.dataset.text;
            navigator.clipboard.writeText(text);
            
            const originalText = btn.textContent;
            btn.textContent = 'Copied!';
            setTimeout(() => {
                btn.textContent = originalText;
            }, 2000);
        }

        function checkDependencies() {
            location.reload();
        }
    </script>
</body>
</html> 
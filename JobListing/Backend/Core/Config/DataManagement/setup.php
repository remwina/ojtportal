<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: text/html');
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/DB_Operations.php';
require_once __DIR__ . '/DatabaseSchema.php';

class SetupManager {
    private const REQUIRED_PHP_VERSION = '7.4.0';
    private const REQUIRED_EXTENSIONS = ['mysqli', 'pdo', 'pdo_mysql', 'mbstring'];
    private string $step;
    private ?string $error = null;
    private ?string $message = null;
    private ?SQL_Operations $dbOps = null;

    public function __construct() {
        $this->step = $_GET['step'] ?? 'check_system';
        
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->validateRequest();
            }
            
            $this->dbOps = new SQL_Operations();
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            $this->step = 'error';
        }

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    private function validateRequest(): void {
        if (!isset($_SESSION['csrf_token']) || !isset($_POST['csrf_token']) || 
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new RuntimeException('Invalid CSRF token');
        }
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    private function checkSystemRequirements(): array {
        $issues = [];
        
        if (version_compare(PHP_VERSION, self::REQUIRED_PHP_VERSION, '<')) {
            $issues[] = "PHP version " . self::REQUIRED_PHP_VERSION . " or higher is required. Current version: " . PHP_VERSION;
        }

        foreach (self::REQUIRED_EXTENSIONS as $ext) {
            if (!extension_loaded($ext)) {
                $issues[] = "Required PHP extension missing: {$ext}";
            }
        }

        try {
            if ($this->dbOps) {
                $this->dbOps->getConnection();
            } else {
                $issues[] = "Database connection not initialized";
            }
        } catch (Exception $e) {
            $issues[] = "Database connection failed: " . $e->getMessage();
        }

        return $issues;
    }

    public function handleSetup(): void {
        try {
            switch ($this->step) {
                case 'check_system':
                    $issues = $this->checkSystemRequirements();
                    if (empty($issues)) {
                        $this->step = 'run_migration';
                        $this->message = "System requirements met!";
                    } else {
                        $this->error = "System requirements not met:\n" . implode("\n", $issues);
                    }
                    break;

                case 'run_migration':
                    if (isset($_POST['confirm'])) {
                        if ($this->dbOps) {
                            $result = $this->dbOps->initDatabase();
                            $this->message = $result['message'];
                            $this->step = 'complete';
                        } else {
                            throw new Exception("Database operations not initialized");
                        }
                    }
                    break;
            }
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            $this->step = 'error';
        }
    }

    public function getStep(): string {
        return $this->step;
    }

    public function getError(): ?string {
        return $this->error;
    }

    public function getMessage(): ?string {
        return $this->message;
    }

    public function getCsrfToken(): string {
        return $_SESSION['csrf_token'];
    }
}

$setupManager = new SetupManager();
$setupManager->handleSetup();
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JobListing System Setup</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');

        :root {
            --primary-red: #ff3b3b;
            --dark-red: #e63535;
            --light-red: #ff6666;
            --white: #ffffff;
            --off-white: #f8f9fc;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }

        body {
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
            background: var(--off-white);
            display: flex;
            align-items: flex-start;
            justify-content: center;
            color: var(--gray-700);
            line-height: 1.5;
            position: relative;
            overflow-y: auto;
            background-color: #fff;
        }

        @media (max-height: 800px) {
            body {
                align-items: flex-start;
                padding: 20px;
            }
            .setup-container {
                margin: 0 auto;
            }
        }

        @media (max-width: 850px) {
            .setup-container {
                margin: 0 20px;
                padding: 1.5rem;
            }
            .terminal {
                word-break: break-all;
                padding-right: 70px;
            }
        }

        @keyframes blob1 {
            0% { transform: translate(0, 0) scale(1); }
            20% { transform: translate(150px, -50px) scale(1.6); }
            40% { transform: translate(-80px, 120px) scale(0.4); }
            60% { transform: translate(100px, 80px) scale(1.8); }
            80% { transform: translate(-120px, -90px) scale(0.5); }
            100% { transform: translate(0, 0) scale(1); }
        }

        @keyframes blob2 {
            0% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(-130px, 60px) scale(1.7); }
            50% { transform: translate(90px, -120px) scale(0.3); }
            75% { transform: translate(-60px, -80px) scale(1.5); }
            100% { transform: translate(0, 0) scale(1); }
        }

        body::before,
        body::after,
        .blob {
            content: '';
            position: fixed;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: linear-gradient(to right, rgba(255, 0, 0, 0.2), rgba(255, 51, 51, 0.15));
            pointer-events: none;
            z-index: 0;
            filter: blur(50px);
        }

        body::before {
            left: calc(50% - 500px);
            top: calc(50% - 400px);
            animation: blob1 12s cubic-bezier(0.4, 0, 0.6, 1) infinite;
            background: linear-gradient(-45deg, rgba(255, 0, 0, 0.25), rgba(255, 51, 51, 0.18));
            z-index: 1;
        }

        body::after {
            right: calc(50% - 500px);
            bottom: calc(50% - 400px);
            animation: blob2 14s cubic-bezier(0.36, 0, 0.64, 1) infinite;
            background: linear-gradient(45deg, rgba(255, 0, 0, 0.3), rgba(255, 51, 51, 0.2));
            z-index: 2;
        }

        h1 {
            color: var(--gray-700);
            margin: 0 0 0.5rem 0;
            font-size: 2.25rem;
            font-weight: 700;
            text-align: center;
            letter-spacing: -0.025em;
            line-height: 1.2;
        }

        .subtitle {
            text-align: center;
            color: var(--gray-600);
            margin-bottom: 2rem;
            font-size: 1rem;
            font-weight: 500;
        }

        .error-message {
            color: var(--primary-red);
            background: rgba(255, 59, 59, 0.1);
            padding: 1rem;
            border-radius: 12px;
            margin: 1rem 0;
        }

        .text-center {
            text-align: center;
        }

        .mb-4 {
            margin-bottom: 1.5rem;
        }

        h3 {
            color: var(--gray-700);
            margin: 0 0 1rem 0;
        }

        ol {
            padding-left: 1.5rem;
        }

        ol li {
            margin-bottom: 1rem;
        }

        .mb-0 {
            margin-bottom: 0;
        }

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
            background: var (--white);
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
            <div class="step-indicator <?php echo in_array($setupManager->getStep(), ['check_system', 'run_migration', 'complete']) ? 'active' : ''; ?>">
                <div class="step-circle">1</div>
                <div class="step-label">System Check</div>
            </div>
            <div class="step-indicator <?php echo in_array($setupManager->getStep(), ['run_migration', 'complete']) ? 'active' : ''; ?>">
                <div class="step-circle">2</div>
                <div class="step-label">Database</div>
            </div>
            <div class="step-indicator <?php echo $setupManager->getStep() === 'complete' ? 'active' : ''; ?>">
                <div class="step-circle">3</div>
                <div class="step-label">Complete</div>
            </div>
        </div>

        <?php if ($setupManager->getError()): ?>
        <div class="error-message">
            <h4>Error Occurred</h4>
            <p><?php echo htmlspecialchars($setupManager->getError()); ?></p>
            <hr>
            <p>Please ensure:</p>
            <ul>
                <li>XAMPP is running (Apache and MySQL)</li>
                <li>MySQL password in DB_Operations.php matches your XAMPP settings</li>
            </ul>
            <a href="setup.php" class="setup-btn">Try Again</a>
        </div>
        <?php endif; ?>

        <div class="step <?php echo $setupManager->getStep() === 'check_system' ? 'active' : ''; ?>">
            <h3>Step 1: System Check</h3>
            <div class="subtitle">
                Checking system requirements...
            </div>
            <a href="?step=run_migration" class="setup-btn">Continue</a>
        </div>

        <div class="step <?php echo $setupManager->getStep() === 'run_migration' ? 'active' : ''; ?>">
            <h3>Step 2: Database Setup</h3>
            <div class="subtitle">
                We'll now set up your database and create an admin account.
                Please ensure XAMPP (Apache and MySQL) is running before proceeding.
            </div>
            <div class="text-center">
                <form method="post" action="?step=run_migration">
                    <input type="hidden" name="csrf_token" value="<?php echo $setupManager->getCsrfToken(); ?>">
                    <button type="submit" name="confirm" class="setup-btn">Set Up Database</button>
                </form>
            </div>
        </div>

        <div class="step <?php echo $setupManager->getStep() === 'complete' ? 'active' : ''; ?>">
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
                    <p><strong>Password:</strong> Admin@123</p>
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
                <a href="../../../../Frontend/login.html" class="setup-btn">Go to Login Page</a>
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
    </script>
</body>
</html>
<div align="center">

# 🚀 Job Placement and Listing Management System Setup

<img src="JobListing/Assets/Images/BatStateU-NEU-Logo.png" width="120">

</div>

---

## 📋 Prerequisites
- 🖥️ XAMPP with Apache and MySQL services
- 🌐 Web browser (Chrome/Firefox recommended)

## 🛠️ Quick Setup Steps

### 1️⃣ Start XAMPP Services
- 📱 Open XAMPP Control Panel
- ▶️ Start both Apache and MySQL services
- ✅ Ensure both services show green status

### 2️⃣ Access Setup Page
- 🌍 Open your web browser
- 📂 Go to: `JobListing/Backend/Core/Config/DataManagement/setup.php`
- ⚙️ The setup script will automatically:
  - 🗃️ Create the database
  - 📊 Initialize required tables
  - 👤 Create default admin account

### 3️⃣ Verify Setup
- ⌛ Wait for "Setup Complete" confirmation
- ✨ You should see success messages for each step

### 4️⃣ Access the System
After successful setup, you can log in at:
- 🔑 `JobListing/Frontend/login.html`

## 👑 Default Admin Account
```yaml
Email    : admin@admin.com
Password : admin123
SR Code  : 21-00001
```

## ⚠️ Troubleshooting

If setup fails:
1. 🔍 Verify XAMPP services are running (Apache and MySQL)
2. 📡 Check if port 3306 is available for MySQL
3. 🔄 Try restarting XAMPP services

### Database Connection Details
```yaml
Username : root
Password : root
Database : joblisting
```

---

<div align="center">

## 💡 Need Help?
Contact system administrator for assistance

</div>
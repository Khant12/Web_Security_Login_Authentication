## HoneyPot Page Setup Guide for XAMPP

### Prerequisites

1. Install XAMPP
   - Download and install XAMPP from [Apache Friends](https://www.apachefriends.org/).

2. Start XAMPP Services
   - Open XAMPP and start **Apache** and **MySQL** services.

---

### Database Setup

1. Access phpMyAdmin
   - Open your browser and go to: [http://localhost/phpmyadmin/]
2. Import Database
   - In phpMyAdmin, click on **New**.
   - Import the `login_db.sql` file (located in the `private` folder of your project).

3. Restart XAMPP Services
   - Stop **Apache** and **MySQL** by clicking **Stop** in XAMPP.
   - Then, start **Apache** and **MySQL** again to restart the database.

---

### Accessing the Web Page

1. Open your browser and navigate to:
   - [http://localhost/login/public/login.php]
---

### Enabling GD Extension for QR Code Generation

1. Verify GD Installation
   - In XAMPP, start **Apache** and **MySQL**, then click on **Admin**.
   - A page will open. Click on the **PHPInfo** option at the top of the page.
   - On the PHPInfo page, search for **gd**. If found, GD is already installed.
   - If GD is not listed, proceed with the steps below.

2. Install GD Extension
   - Open `php.ini` file in your editor.
   - Search for `;extension=gd`.
   - Remove the semicolon (`;`) from `;extension=gd` to enable the extension.
   - Save the file.

3. Copy the GD DLL File
   - Go to your XAMPP directory, typically located in `C:\xampp`.
   - In the `php\ext` folder, find `php_gd.dll`.
   - Copy `php_gd.dll` to `C:\Windows\System32`.

4. Restart XAMPP
   - Restart the **Apache** service from XAMPP.
   - Visit [http://localhost/dashboard/phpinfo.php] to verify GD is installed.

---

### Accessing the Web Page

After setting up GD, you can access the web page by navigating to:
- [http://localhost/Web_Security_Login_Authentication/public/login.php]

---

### Troubleshooting: MySQL Shutdown Unexpectedly

If you encounter the error **"MySQL shutdown unexpectedly"**, follow the guide provided by Kinsta to resolve it:
- [Kinsta Knowledgebase: MySQL Shutdown Unexpectedly](https://kinsta.com/knowledgebase/xampp-mysql-shutdown-unexpectedly/)

---

### Step 1: Install Google Authenticator App

1. Download the Google Authenticator App:
   - **For Android**: Go to the [Google Play Store](https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2) and search for **Google Authenticator**.
   - **For iOS**: Go to the [App Store](https://apps.apple.com/us/app/google-authenticator/id388497605) and search for **Google Authenticator**.
   
2. Install the App:
   - Follow the on-screen instructions to install the app on your smartphone.


### Step 2: Scan the QR Code with Google Authenticator

1. Open Google Authenticator:
   - Open the Google Authenticator app on your smartphone.

2. Scan the QR Code:
   - Tap the "+" icon in the Google Authenticator app to add a new account.
   - Select "Scan a QR code".
   - Use your phone’s camera to scan the QR code displayed on your web page.

   The app will add the account and start generating OTPs.
   
--- 

### Changing Admin Role in Database

To manually set a user’s role as **admin**, follow these steps:

1. Open phpMyAdmin
   - Go to [http://localhost/phpmyadmin/] in your browser.
   
2. Select the Database
   - On the left sidebar, select the database you've imported (usually named `login_db` or as specified in your project).

3. Access the Users Table
   - In the database, click on the **Users** table (or the table that stores user information, if named differently).
   
4. Update the Role to Admin
   - Find the user you want to update (e.g., a user with the `user` role).
   - Click **Edit** next to that user’s entry.
   - Look for the **role** column and change the value from `'user'` to `'admin'`.
   - Example:
     - **role**: `admin`
   - Click **Go** or **Save** to update the user’s role.

--- 

### Further Questions

If you have any further questions or need assistance, please contact us at:  
Email: [testingotp234@gmail.com]


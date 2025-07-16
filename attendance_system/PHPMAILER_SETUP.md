# PHPMailer Setup Guide for College Attendance System

This guide will help you set up PHPMailer for the College Attendance Management System. PHPMailer is now the default email library replacing the PHP mail() function.

## Why PHPMailer?

- **More Reliable**: Better than PHP's built-in mail() function
- **SMTP Support**: Full SMTP authentication and encryption
- **Error Handling**: Better error reporting and debugging
- **Security**: Built-in protection against email injection attacks
- **Modern**: Actively maintained with regular updates

## Installation Methods

### Method 1: Composer Installation (Recommended)

1. **Install Composer** (if not already installed):
   ```bash
   # Download and install Composer
   curl -sS https://getcomposer.org/installer | php
   sudo mv composer.phar /usr/local/bin/composer
   ```

2. **Install PHPMailer via Composer**:
   ```bash
   cd attendance_system
   composer install
   ```

3. **Verify Installation**:
   - Check that `vendor/` directory exists
   - Verify `vendor/phpmailer/phpmailer/` contains PHPMailer files

### Method 2: Manual Installation

1. **Download PHPMailer**:
   - Go to [PHPMailer GitHub](https://github.com/PHPMailer/PHPMailer)
   - Download the latest release (ZIP file)

2. **Extract and Place Files**:
   ```
   attendance_system/
   ├── includes/
   │   ├── phpmailer/
   │   │   ├── PHPMailer.php
   │   │   ├── SMTP.php
   │   │   ├── Exception.php
   │   │   └── (other PHPMailer files)
   │   └── phpmailer_setup.php
   ```

3. **File Structure**:
   ```
   includes/phpmailer/
   ├── PHPMailer.php
   ├── SMTP.php
   ├── Exception.php
   ├── OAuth.php
   ├── POP3.php
   └── DSNConfigurator.php
   ```

## Configuration

### Step 1: Edit PHPMailer Configuration

Edit `includes/phpmailer_setup.php`:

```php
// SMTP Configuration Constants
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls'); // 'tls' or 'ssl'
define('SMTP_AUTH', true);
define('SMTP_USERNAME', 'your_email@gmail.com'); // Your Gmail address
define('SMTP_PASSWORD', 'your_app_password'); // Your Gmail App Password
define('FROM_EMAIL', 'your_email@gmail.com'); // Your Gmail address
define('FROM_NAME', 'College Attendance System');
```

### Step 2: Gmail Configuration

#### Enable 2-Factor Authentication:
1. Go to [Google Account](https://myaccount.google.com/)
2. Security → 2-Step Verification
3. Turn on 2-Step Verification

#### Generate App Password:
1. Go to [Google Account](https://myaccount.google.com/)
2. Security → App passwords
3. Select "Mail" and generate password
4. Use this password in `SMTP_PASSWORD` (not your regular Gmail password)

#### Gmail Security Settings:
- Enable 2-Factor Authentication (required for App Passwords)
- Generate App Password for "Mail"
- Use App Password in configuration

### Step 3: Other Email Providers

#### Outlook/Hotmail:
```php
define('SMTP_HOST', 'smtp.office365.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
```

#### Yahoo Mail:
```php
define('SMTP_HOST', 'smtp.mail.yahoo.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
```

#### Custom SMTP:
```php
define('SMTP_HOST', 'mail.yourdomain.com');
define('SMTP_PORT', 587); // or 465 for SSL
define('SMTP_SECURE', 'tls'); // or 'ssl'
```

## Testing

### Test Email Configuration:

1. **Open Test Script**:
   ```
   http://localhost/attendance_system/test_email.php
   ```

2. **Check Configuration Status**:
   - PHPMailer availability
   - SMTP settings
   - Configuration status

3. **Send Test Email**:
   - Enter your email address
   - Click "Send Test Email"
   - Check your inbox (and spam folder)

### Manual Testing:

```php
<?php
require_once 'includes/config.php';

// Test email
$result = testEmailConfiguration('your_email@gmail.com');
if ($result) {
    echo "Email configuration working!";
} else {
    echo "Email configuration failed!";
}
?>
```

## Troubleshooting

### Common Issues:

#### 1. "PHPMailer library is not available"
**Solution**: Install PHPMailer via Composer or manually

#### 2. "SMTP Error: Authentication failed"
**Solutions**:
- Use App Password instead of regular Gmail password
- Enable 2-Factor Authentication
- Check username/password in configuration

#### 3. "SMTP connect() failed"
**Solutions**:
- Check SMTP host and port
- Verify internet connection
- Check firewall settings

#### 4. "SSL certificate problem"
**Solutions**:
- Update PHP and OpenSSL
- Use TLS instead of SSL
- Check server SSL configuration

#### 5. "Could not instantiate mail function"
**Solutions**:
- Verify PHPMailer files are in correct location
- Check file permissions
- Ensure includes are working

### Debug Mode:

Enable debug mode in `phpmailer_setup.php`:

```php
$mail->SMTPDebug = 2; // Enable verbose debug output
$mail->Debugoutput = 'html'; // HTML debug output
```

### Log Files:

Check error logs:
- Server error logs
- PHP error logs
- Custom application logs

## Security Best Practices

### 1. Use App Passwords:
- Never use your regular email password
- Generate specific App Passwords for applications

### 2. Environment Variables:
```php
// Use environment variables for sensitive data
define('SMTP_USERNAME', getenv('SMTP_USERNAME'));
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD'));
```

### 3. Secure Configuration:
- Store credentials outside web root
- Use HTTPS for admin panels
- Regularly update PHPMailer

### 4. Email Validation:
- Validate email addresses before sending
- Sanitize input data
- Rate limit email sending

## Advanced Configuration

### Custom Email Templates:
Modify templates in `phpmailer_setup.php`:
- OTP email template
- Password reset template
- Test email template

### Multiple Email Providers:
```php
// Fallback SMTP configuration
$smtp_configs = [
    'primary' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'primary@gmail.com',
        'password' => 'app_password1'
    ],
    'backup' => [
        'host' => 'smtp.office365.com',
        'port' => 587,
        'username' => 'backup@outlook.com',
        'password' => 'app_password2'
    ]
];
```

### Email Queue:
For high-volume applications, consider implementing email queues:
- Database-based queue
- Redis queue
- RabbitMQ integration

## Maintenance

### Regular Updates:
```bash
# Update PHPMailer via Composer
composer update phpmailer/phpmailer
```

### Monitor Email Delivery:
- Check bounce rates
- Monitor spam folder placement
- Track email delivery success

### Backup Configuration:
- Store email configurations securely
- Document setup procedures
- Test backup procedures

## Support

### Getting Help:
- Check [PHPMailer Documentation](https://phpmailer.github.io/PHPMailer/)
- Visit [PHPMailer GitHub Issues](https://github.com/PHPMailer/PHPMailer/issues)
- Test with the included test script

### System Requirements:
- PHP 7.4 or higher
- OpenSSL extension
- cURL extension (optional)
- Internet connection for SMTP

---

**Note**: This system now uses PHPMailer by default. The old mail() function has been completely replaced for better reliability and security.
# Email Setup Guide for OTP System

This guide will help you configure email settings for the OTP verification system. The system supports various email providers and SMTP configurations.

## Quick Setup

### 1. Basic Configuration (Built-in PHP mail)
Edit `includes/config.php` and update these settings:

```php
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your_email@college.edu');
define('SMTP_PASSWORD', 'your_password');
define('FROM_EMAIL', 'noreply@college.edu');
define('FROM_NAME', 'College Attendance System');
```

### 2. Production Setup (PHPMailer - Recommended)
For production environments, use PHPMailer for better email delivery:

```bash
composer require phpmailer/phpmailer
```

Then uncomment the PHPMailer code in `includes/phpmailer_config.php`

## Email Provider Configurations

### Gmail SMTP
```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your_email@gmail.com');
define('SMTP_PASSWORD', 'your_app_password'); // Use App Password
define('FROM_EMAIL', 'your_email@gmail.com');
define('FROM_NAME', 'College Attendance System');
```

**Gmail Setup Steps:**
1. Enable 2-factor authentication on your Google account
2. Go to Google Account Settings > Security > 2-Step Verification
3. Generate an "App Password" for this application
4. Use the App Password instead of your regular password

### Microsoft Office 365 / Outlook.com
```php
define('SMTP_HOST', 'smtp.office365.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your_email@yourdomain.com');
define('SMTP_PASSWORD', 'your_password');
define('FROM_EMAIL', 'your_email@yourdomain.com');
define('FROM_NAME', 'College Attendance System');
```

### Yahoo Mail
```php
define('SMTP_HOST', 'smtp.mail.yahoo.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your_email@yahoo.com');
define('SMTP_PASSWORD', 'your_app_password');
define('FROM_EMAIL', 'your_email@yahoo.com');
define('FROM_NAME', 'College Attendance System');
```

### Custom/Institutional Email Server
```php
define('SMTP_HOST', 'mail.yourdomain.com');
define('SMTP_PORT', 587); // or 465 for SSL
define('SMTP_USERNAME', 'noreply@yourdomain.com');
define('SMTP_PASSWORD', 'your_password');
define('FROM_EMAIL', 'noreply@yourdomain.com');
define('FROM_NAME', 'College Attendance System');
```

## Security Considerations

### 1. Use App Passwords
- **Gmail**: Use App Passwords instead of your main password
- **Yahoo**: Generate App Passwords for third-party applications
- **Office 365**: Use your regular password or App Password if 2FA is enabled

### 2. Email Security
- Always use TLS/SSL encryption (port 587 or 465)
- Use a dedicated email account for system notifications
- Regularly rotate email passwords
- Monitor email delivery logs

### 3. Rate Limiting
Consider implementing rate limiting to prevent spam:

```php
// Add to config.php
define('OTP_RATE_LIMIT', 3); // Max 3 OTP requests per hour
define('OTP_RATE_WINDOW', 3600); // 1 hour window
```

## Testing Email Configuration

### 1. Test Email Delivery
Create a test script to verify email configuration:

```php
<?php
require_once 'includes/config.php';

// Test email
$test_email = 'test@example.com';
$test_otp = '123456';
$test_name = 'Test User';

if (sendOTP($test_email, $test_otp, $test_name)) {
    echo "Email sent successfully!";
} else {
    echo "Email delivery failed. Check your configuration.";
}
?>
```

### 2. Check Email Logs
Monitor server logs for email delivery issues:

```bash
# Check PHP error logs
tail -f /var/log/php_errors.log

# Check mail logs (Linux)
tail -f /var/log/mail.log
```

## Email Template Customization

### 1. Modify Email Content
Edit the email template in `includes/config.php` or `includes/phpmailer_config.php`:

```php
function getOTPEmailTemplate($otp, $student_name) {
    // Customize the HTML template here
    return "Your custom email template with OTP: $otp";
}
```

### 2. Add College Branding
- Update colors to match your college theme
- Add college logo to the email header
- Customize footer with college information

### 3. Multi-language Support
```php
function getOTPEmailTemplate($otp, $student_name, $language = 'en') {
    switch($language) {
        case 'es':
            return getSpanishEmailTemplate($otp, $student_name);
        case 'fr':
            return getFrenchEmailTemplate($otp, $student_name);
        default:
            return getEnglishEmailTemplate($otp, $student_name);
    }
}
```

## Common Issues and Solutions

### 1. Email Not Sending
- Check SMTP credentials and server settings
- Verify firewall doesn't block SMTP ports
- Ensure PHP `mail()` function is enabled
- Check server email configuration

### 2. Emails Going to Spam
- Use proper SPF, DKIM, and DMARC records
- Use a reputable email service provider
- Include proper unsubscribe links
- Avoid spam trigger words in subject/content

### 3. Slow Email Delivery
- Use asynchronous email sending
- Implement email queuing system
- Use dedicated email service (SendGrid, Mailgun, etc.)

### 4. Gmail App Password Issues
- Ensure 2-factor authentication is enabled
- Generate new App Password specifically for this application
- Use the 16-character App Password without spaces

## Production Recommendations

### 1. Use Professional Email Service
Consider using dedicated email services for production:

- **SendGrid**: High deliverability, detailed analytics
- **Mailgun**: Developer-friendly API
- **Amazon SES**: Cost-effective for high volumes
- **Postmark**: Focus on transactional emails

### 2. Email Queue System
Implement email queuing for better performance:

```php
// Store emails in database queue
function queueOTP($email, $otp, $student_name) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO email_queue (email, otp, student_name, created_at) VALUES (?, ?, ?, NOW())");
    return $stmt->execute([$email, $otp, $student_name]);
}

// Process queue via cron job
// php process_email_queue.php
```

### 3. Monitoring and Logging
- Monitor email delivery rates
- Log failed email attempts
- Set up alerts for email delivery issues
- Track OTP usage and success rates

## Environment-Specific Configuration

### Development Environment
```php
// Log emails instead of sending
function sendOTP($email, $otp, $student_name) {
    error_log("DEV: OTP for $email: $otp");
    return true;
}
```

### Staging Environment
```php
// Redirect all emails to test address
define('TEST_EMAIL_OVERRIDE', 'test@yourdomain.com');
```

### Production Environment
```php
// Use full email configuration with monitoring
define('EMAIL_MONITORING', true);
define('EMAIL_ALERTS', 'admin@yourdomain.com');
```

## Backup Email Configuration

Set up a backup email method in case the primary fails:

```php
function sendOTP($email, $otp, $student_name) {
    // Try primary email method
    if (sendPrimaryEmail($email, $otp, $student_name)) {
        return true;
    }
    
    // Fallback to backup method
    return sendBackupEmail($email, $otp, $student_name);
}
```

## Support and Troubleshooting

### Common Error Messages
- **Authentication failed**: Check username/password
- **Connection refused**: Verify SMTP host and port
- **SSL/TLS errors**: Check security settings
- **Rate limit exceeded**: Implement proper rate limiting

### Getting Help
- Check your email provider's documentation
- Verify server email configuration
- Test with a simple email script first
- Contact your hosting provider for server-specific issues

---

For additional support, consult your email provider's documentation or contact the system administrator.
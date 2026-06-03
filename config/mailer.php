<?php
/**
 * mailer.php — Pre-configured email sender for MDCI Smart Vehicle System
 * Uses bundled PHPMailer (no Composer, no installation needed).
 *
 * ════════════════════════════════════════════════════════════════════
 *  ADMIN SETUP — Only ONE person (the system admin) needs to do this:
 *
 *  1. Create a dedicated Gmail for the system, e.g.:
 *       mdci.smartvehicle@gmail.com
 *
 *  2. Enable 2-Step Verification on that Gmail account:
 *       https://myaccount.google.com/security
 *
 *  3. Generate an App Password:
 *       https://myaccount.google.com/apppasswords
 *       → Select "Mail" + "Windows Computer" → Generate
 *       → Copy the 16-character password
 *
 *  4. Paste the email and App Password in the two lines below.
 *     That's it — students and teachers never need to touch anything.
 * ════════════════════════════════════════════════════════════════════
 */

// ── CHANGE ONLY THESE TWO LINES ──────────────────────────────────────────────
define('SYSTEM_EMAIL',    'naigvillegas@gmail.com'); // ← system Gmail
define('SYSTEM_APP_PASS', 'vsfw ozib njah ywax');         // ← Gmail App Password
// ─────────────────────────────────────────────────────────────────────────────

define('SYSTEM_NAME',               'MDCI Smart Vehicle System');
define('RESET_TOKEN_EXPIRY_MINUTES', 30);
define('APP_BASE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/SmartVehicleAuthPass');


/**
 * Send a password reset email.
 * Automatically uses bundled PHPMailer — no Composer needed.
 */
function sendPasswordResetEmail(string $toEmail, string $toName, string $resetLink, string $userType): bool
{
    // Load bundled PHPMailer
    require_once __DIR__ . '/phpmailer/Exception.php';
    require_once __DIR__ . '/phpmailer/PHPMailer.php';
    require_once __DIR__ . '/phpmailer/SMTP.php';

    $roleLabel  = $userType === 'student' ? 'Student' : 'Teacher / Staff';
    $themeColor = $userType === 'student' ? '#d97706' : '#059669';
    $subject    = 'Password Reset Request – MDCI Smart Vehicle System';

    $htmlBody = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:40px 0;">
<tr><td align="center">
<table width="100%" style="max-width:520px;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">

  <!-- Header -->
  <tr><td style="background:{$themeColor};padding:36px 40px;text-align:center;">
    <div style="font-size:42px;margin-bottom:10px;">🔑</div>
    <h1 style="margin:0;color:#fff;font-size:22px;font-weight:800;">Password Reset Request</h1>
    <p style="margin:6px 0 0;color:rgba(255,255,255,.85);font-size:13px;">MDCI Smart Vehicle Authentication Pass System</p>
  </td></tr>

  <!-- Body -->
  <tr><td style="padding:36px 40px;">
    <p style="margin:0 0 6px;color:#374151;font-size:15px;">Hello, <strong>{$toName}</strong> <span style="color:#6b7280;font-size:13px;">({$roleLabel})</span></p>
    <p style="margin:0 0 22px;color:#6b7280;font-size:14px;line-height:1.65;">
      We received a request to reset the password for your account.<br>
      Click the button below — the link is valid for <strong>30 minutes</strong>.
    </p>

    <!-- CTA Button -->
    <table width="100%" cellpadding="0" cellspacing="0">
    <tr><td align="center" style="padding:4px 0 28px;">
      <a href="{$resetLink}"
         style="display:inline-block;background:{$themeColor};color:#fff;text-decoration:none;
                padding:15px 40px;border-radius:12px;font-size:15px;font-weight:700;letter-spacing:.3px;">
        Reset My Password
      </a>
    </td></tr>
    </table>

    <!-- Safety notice -->
    <div style="background:#fef9c3;border-left:4px solid #f59e0b;border-radius:8px;padding:14px 16px;margin-bottom:22px;">
      <p style="margin:0;color:#92400e;font-size:13px;line-height:1.55;">
        <strong>⚠️ Didn't request this?</strong><br>
        If you did <em>not</em> ask to reset your password, you can safely ignore this email —
        your password will <strong>not</strong> be changed, and no one can reset it without clicking the link.
      </p>
    </div>

    <p style="margin:0 0 6px;color:#9ca3af;font-size:12px;">Button not working? Copy this link into your browser:</p>
    <p style="margin:0;word-break:break-all;">
      <a href="{$resetLink}" style="color:{$themeColor};font-size:12px;">{$resetLink}</a>
    </p>
  </td></tr>

  <!-- Footer -->
  <tr><td style="background:#f8fafc;padding:18px 40px;border-top:1px solid #e5e7eb;text-align:center;">
    <p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.6;">
      This is an automated message from the MDCI Smart Vehicle Authentication Pass System.<br>
      Please do not reply to this email.
    </p>
  </td></tr>

</table>
</td></tr>
</table>
</body>
</html>
HTML;

    $plainText =
        "Hello {$toName} ({$roleLabel}),\n\n" .
        "We received a request to reset your password.\n\n" .
        "Reset link (valid for 30 minutes):\n{$resetLink}\n\n" .
        "If you did NOT request this, ignore this email — your password won't change.\n\n" .
        "– MDCI Smart Vehicle System";

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = SYSTEM_EMAIL;
        $mail->Password   = SYSTEM_APP_PASS;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // Sender & recipient
        $mail->setFrom(SYSTEM_EMAIL, SYSTEM_NAME);
        $mail->addAddress($toEmail, $toName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $plainText;

        $mail->send();
        return true;

    } catch (\Exception $e) {
        error_log('Mailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}


/**
 * Send 6-digit verification code email.
 */
function sendCodeEmail(string $toEmail, string $toName, string $code, string $userType): bool
{
    require_once __DIR__ . '/phpmailer/Exception.php';
    require_once __DIR__ . '/phpmailer/PHPMailer.php';
    require_once __DIR__ . '/phpmailer/SMTP.php';

    $roleLabel  = $userType === 'student' ? 'Student' : 'Teacher / Staff';
    $themeColor = $userType === 'student' ? '#d97706' : '#059669';
    $digits     = str_split($code);

    $digitHtml = implode('', array_map(fn($d) =>
        "<span style=\"display:inline-block;width:52px;height:64px;line-height:64px;
                       margin:0 4px;background:#f9fafb;border:2px solid {$themeColor};
                       border-radius:14px;font-size:30px;font-weight:900;
                       text-align:center;color:#111827;\">$d</span>",
    $digits));

    $htmlBody = <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:40px 0;">
<tr><td align="center">
<table width="100%" style="max-width:520px;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">

  <tr><td style="background:{$themeColor};padding:32px 40px;text-align:center;">
    <div style="font-size:40px;margin-bottom:10px;">🔐</div>
    <h1 style="margin:0;color:#fff;font-size:22px;font-weight:800;">Verify It's You</h1>
    <p style="margin:6px 0 0;color:rgba(255,255,255,.85);font-size:13px;">MDCI Smart Vehicle Authentication Pass System</p>
  </td></tr>

  <tr><td style="padding:36px 40px;">
    <p style="margin:0 0 6px;color:#374151;font-size:15px;">Hello, <strong>{$toName}</strong> <span style="color:#6b7280;font-size:13px;">({$roleLabel})</span></p>
    <p style="margin:0 0 26px;color:#6b7280;font-size:14px;line-height:1.65;">
      Use the verification code below to reset your password.<br>
      <strong>This code expires in 10 minutes.</strong>
    </p>

    <p style="text-align:center;color:#6b7280;font-size:13px;margin:0 0 12px;font-weight:600;letter-spacing:.5px;">YOUR VERIFICATION CODE</p>
    <div style="text-align:center;margin-bottom:28px;">{$digitHtml}</div>

    <div style="background:#fef9c3;border-left:4px solid #f59e0b;border-radius:8px;padding:14px 16px;">
      <p style="margin:0;color:#92400e;font-size:13px;line-height:1.55;">
        <strong>⚠️ Didn't request this?</strong><br>
        If you did not ask to reset your password, ignore this email — your password will not change.
        Do not share this code with anyone.
      </p>
    </div>
  </td></tr>

  <tr><td style="background:#f8fafc;padding:18px 40px;border-top:1px solid #e5e7eb;text-align:center;">
    <p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.6;">
      Automated message from MDCI Smart Vehicle Authentication Pass System.<br>
      Do not reply to this email.
    </p>
  </td></tr>

</table></td></tr></table>
</body></html>
HTML;

    $plain = "Hello {$toName} ({$roleLabel}),\n\n"
           . "Your password reset verification code is:\n\n  {$code}\n\n"
           . "This code expires in 10 minutes.\n\n"
           . "If you did not request this, ignore this email.\n\n"
           . "– MDCI Smart Vehicle System";

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = SYSTEM_EMAIL;
        $mail->Password   = SYSTEM_APP_PASS;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom(SYSTEM_EMAIL, SYSTEM_NAME);
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = 'Your Password Reset Code – MDCI Smart Vehicle System';
        $mail->Body    = $htmlBody;
        $mail->AltBody = $plain;
        $mail->send();
        return true;
    } catch (\Exception $e) {
        error_log('Mailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}

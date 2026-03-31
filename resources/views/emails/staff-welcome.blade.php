<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Welcome to Cureva</title>
</head>
<body style="margin:0;padding:24px;background:#f4f7f6;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;margin:0 auto;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #dce7e3;">
    <tr>
      <td style="padding:24px 32px;background:#0f766e;color:#ffffff;">
        <div style="font-size:22px;font-weight:700;letter-spacing:0.2px;">Cureva</div>
        <div style="font-size:14px;opacity:0.92;margin-top:6px;">Welcome to your staff account</div>
      </td>
    </tr>
    <tr>
      <td style="padding:32px;">
        <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">
          Hello {{ $user->first_name }},
        </p>

        <p style="margin:0 0 16px;font-size:15px;line-height:1.7;">
          Your Cureva account has been created successfully as
          <strong>{{ $user->role }}</strong>.
        </p>

        @if(!empty($scopeLabel))
          <p style="margin:0 0 16px;font-size:15px;line-height:1.7;">
            Assignment: <strong>{{ $scopeLabel }}</strong>
          </p>
        @endif

        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:20px 0;background:#f8fbfa;border:1px solid #dce7e3;border-radius:12px;">
          <tr>
            <td style="padding:20px;">
              <div style="font-size:14px;line-height:1.8;">
                <div><strong>Username:</strong> {{ $user->username }}</div>
                <div><strong>Password:</strong> {{ $plainPassword }}</div>
              </div>
            </td>
          </tr>
        </table>

        <p style="margin:0;font-size:13px;line-height:1.7;color:#6b7280;">
          Please keep these credentials secure.
        </p>
      </td>
    </tr>
  </table>
</body>
</html>

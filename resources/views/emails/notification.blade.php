<!DOCTYPE html>
<html lang="th">
<head><meta charset="utf-8"></head>
<body style="margin:0; background:#f4f6f9; font-family:'Segoe UI',Tahoma,sans-serif; color:#1f2937;">
    <div style="max-width:520px; margin:24px auto; background:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #e3e8ef;">
        <div style="background:#1e3a5f; color:#fff; padding:18px 24px; font-weight:700; font-size:16px;">
            {{ config('app.name') }}
        </div>
        <div style="padding:24px;">
            <h2 style="margin:0 0 12px; font-size:18px; color:#1f2937;">{{ $subjectLine }}</h2>
            <p style="margin:0 0 18px; font-size:14px; line-height:1.6; color:#5b6675;">{{ $bodyText }}</p>
            @if($url)
                <a href="{{ $url }}" style="display:inline-block; background:#2563eb; color:#fff; text-decoration:none; padding:10px 18px; border-radius:7px; font-size:14px;">เปิดในระบบ</a>
            @endif
        </div>
        <div style="padding:14px 24px; background:#fafbfc; border-top:1px solid #e3e8ef; font-size:12px; color:#8a94a6;">
            อีเมลนี้ส่งโดยอัตโนมัติจากระบบ {{ config('hrpro.company_name') }} — กรุณาอย่าตอบกลับ
        </div>
    </div>
</body>
</html>

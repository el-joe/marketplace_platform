<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تمت الموافقة على حسابك</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
            direction: rtl;
        }

        .wrapper {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }

        .header {
            background: #f0b429;
            padding: 32px 40px;
            text-align: center;
        }

        .header h1 {
            color: #1a1a1a;
            font-size: 22px;
            margin: 0;
        }

        .body {
            padding: 36px 40px;
            color: #333;
            font-size: 15px;
            line-height: 1.8;
        }

        .name {
            font-size: 20px;
            font-weight: 700;
            color: #1a1a1a;
            margin: 0 0 16px;
        }

        .highlight-box {
            background: #fffbf0;
            border: 1px solid #f0b429;
            border-radius: 6px;
            padding: 16px 20px;
            margin: 20px 0;
        }

        .footer {
            background: #f9f9f9;
            padding: 24px 40px;
            text-align: center;
            font-size: 13px;
            color: #999;
            border-top: 1px solid #eee;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="header">
            <h1>🎉 تمت الموافقة على حسابك!</h1>
        </div>
        <div class="body">
            <p class="name">مرحباً، {{ $marketer->name }}</p>

            <p>يسعدنا إخبارك بأن طلبك للانضمام كمسوّق على منصتنا قد تمت مراجعته والموافقة عليه.</p>

            <div class="highlight-box">
                <strong>تفاصيل حسابك:</strong><br>
                الاسم: <strong>{{ $marketer->name }}</strong><br>
                البريد الإلكتروني: <strong>{{ $marketer->email }}</strong><br>
                تاريخ التفعيل: <strong>{{ now()->format('Y-m-d') }}</strong>
            </div>

            <p>يمكنك الآن تسجيل الدخول إلى حسابك والبدء في إنشاء روابطك التسويقية وتتبع عمولاتك.</p>

            <p>إذا كان لديك أي استفسار لا تتردد في التواصل معنا.</p>

            <p style="margin-top:24px;">مع تحياتنا،<br><strong>فريق المنصة</strong></p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} — جميع الحقوق محفوظة
        </div>
    </div>
</body>

</html>

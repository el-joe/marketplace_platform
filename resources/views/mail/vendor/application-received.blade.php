<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طلبك قيد المراجعة</title>
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

        .header img {
            height: 40px;
        }

        .header h1 {
            color: #1a1a1a;
            font-size: 22px;
            margin: 12px 0 0;
        }

        .body {
            padding: 36px 40px;
            color: #333;
            font-size: 15px;
            line-height: 1.8;
        }

        .store-name {
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

        .steps {
            padding: 0;
            margin: 20px 0;
            list-style: none;
        }

        .steps li {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .steps li:last-child {
            border-bottom: none;
        }

        .step-num {
            background: #f0b429;
            color: #1a1a1a;
            font-weight: 700;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 12px;
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
            <h1>🎉 تم استلام طلبك!</h1>
        </div>
        <div class="body">
            <p class="store-name">مرحباً، {{ $vendor->name }}</p>

            <p>شكراً لتسجيلك كبائع على منصة <strong>نون</strong>. استلمنا طلبك بنجاح وسيقوم فريقنا بمراجعته قريباً.</p>

            <div class="highlight-box">
                <strong>تفاصيل طلبك:</strong><br>
                اسم المتجر: <strong>{{ $vendor->store_name }}</strong><br>
                البريد الإلكتروني: <strong>{{ $vendor->email }}</strong><br>
                تاريخ التقديم: <strong>{{ now()->format('Y-m-d') }}</strong>
            </div>

            <p>ما الذي سيحدث بعد ذلك؟</p>
            <ul class="steps">
                <li>
                    <span class="step-num">١</span>
                    <span>سيراجع فريقنا طلبك والوثائق المرفقة خلال <strong>٣–٥ أيام عمل</strong>.</span>
                </li>
                <li>
                    <span class="step-num">٢</span>
                    <span>في حال الموافقة، ستتلقى بريداً إلكترونياً يتضمن بيانات الدخول إلى لوحة تحكم البائع.</span>
                </li>
                <li>
                    <span class="step-num">٣</span>
                    <span>يمكنك بعدها إضافة منتجاتك والبدء بالبيع فوراً.</span>
                </li>
            </ul>

            <p>إذا كان لديك أي استفسار لا تتردد في التواصل معنا على <a href="mailto:vendors@noon.com"
                    style="color:#f0b429;">vendors@noon.com</a></p>

            <p style="margin-top:24px;">مع تحياتنا،<br><strong>فريق نون للبائعين</strong></p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} نون — جميع الحقوق محفوظة
        </div>
    </div>
</body>

</html>
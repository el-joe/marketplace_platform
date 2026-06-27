<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحديث بشأن طلبك</title>
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
            background: #e53e3e;
            padding: 32px 40px;
            text-align: center;
        }

        .header h1 {
            color: #fff;
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

        .reason-box {
            background: #fff5f5;
            border: 1px solid #fc8181;
            border-radius: 6px;
            padding: 16px 20px;
            margin: 20px 0;
            color: #742a2a;
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
            <h1>تحديث بشأن طلب انضمامك</h1>
        </div>
        <div class="body">
            <p class="name">عزيزي/عزيزتي {{ $marketer->name }}</p>

            <p>شكراً لاهتمامك بالانضمام إلى منصتنا كمسوّق. بعد مراجعة طلبك، نأسف لإعلامك بأنه لا يمكننا قبول طلبك في الوقت الحالي.</p>

            <div class="reason-box">
                <strong>سبب الرفض:</strong><br>
                {{ $reason }}
            </div>

            <p>إذا كنت تعتقد أن هناك خطأً أو لديك معلومات إضافية تودّ مشاركتها، يسعدنا التواصل معك.</p>

            <p style="margin-top:24px;">مع تحياتنا،<br><strong>فريق المنصة</strong></p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} — جميع الحقوق محفوظة
        </div>
    </div>
</body>

</html>

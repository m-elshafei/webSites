<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مقارنة أحجام وصيغ الصور | Original vs Compressed vs WebP</title>
    <link rel="stylesheet" href="{{ asset('css/image-comparison.css') }}">
</head>
<body>
    <main class="page-container">
        <nav class="top-nav">
            <a href="{{ url('/') }}" class="top-nav-link">الرئيسية</a>
            @if (Route::has('dashboard'))
                <a href="{{ route('dashboard') }}" class="top-nav-link">لوحة التحكم (Dashboard)</a>
            @endif
        </nav>

        <header class="hero-header">
            <h1 class="hero-title">مقارنة أداء وصيغ الصور</h1>
            <p class="hero-subtitle">
                تجربة عملية توضح الفرق بين الصورة الأصلية، والنسخة المضغوطة بنفس الصيغة، ونسخة WebP الحديثة مع قياس الحجم ونسبة التوفير بدقة.
            </p>
        </header>

        <section class="upload-card">
            <form action="{{ route('image.comparison.store') }}" method="POST" enctype="multipart/form-data" class="upload-form">
                @csrf
                <div class="file-input-wrapper">
                    <input type="file" name="image" id="imageInput" class="file-input" accept="image/jpeg,image/png,image/webp" required>
                </div>

                @if (isset($errors) && $errors->any())
                    <div class="alert-error" role="alert">
                        <strong>حدث خطأ في التحقق من الملف:</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <button type="submit" class="upload-btn">
                    معالجة ومقارنة الصورة
                </button>
            </form>
        </section>

        @if (!empty($comparisons))
            <section class="results-section">
                <header class="results-header">
                    <h2 class="results-title">نتائج المقارنة بين الحالات الثلاث</h2>
                    <p class="results-subtitle">تم حفظ ومعالجة النسخ الثلاث بنجاح في وحدة التخزين.</p>
                </header>

                <div class="comparison-grid">
                    @foreach ($comparisons as $item)
                        <article class="comparison-card @if($item['is_winner']) winner @endif">
                            @if ($item['is_winner'])
                                <span class="winner-ribbon">أعلى نسبة توفير</span>
                            @endif

                            <div class="image-preview-container">
                                <img src="{{ $item['url'] }}" alt="{{ $item['title'] }}" class="comparison-image" loading="lazy">
                            </div>

                            <div class="card-content">
                                <div class="card-title-group">
                                    <h3 class="card-title">{{ $item['title'] }}</h3>
                                    <span class="format-badge">{{ $item['format'] }}</span>
                                </div>

                                <p class="card-subtitle">{{ $item['subtitle'] }}</p>

                                <div class="metrics-list">
                                    <div class="metric-row">
                                        <span class="metric-label">الأبعاد:</span>
                                        <span class="metric-value">{{ $item['dimensions'] }} px</span>
                                    </div>
                                    <div class="metric-row">
                                        <span class="metric-label">حجم الملف:</span>
                                        <span class="metric-value">{{ $item['size_kb'] }} KB</span>
                                    </div>
                                    <div class="metric-row">
                                        <span class="metric-label">نسبة التوفير:</span>
                                        <span class="savings-badge @if($item['savings_percent'] > 0) savings-positive @elseif($item['savings_percent'] < 0) savings-negative @else savings-zero @endif">
                                            @if ($item['savings_percent'] > 0)
                                                -{{ $item['savings_percent'] }}%
                                            @elseif ($item['savings_percent'] < 0)
                                                +{{ abs($item['savings_percent']) }}%
                                            @else
                                                0% (الأصل)
                                            @endif
                                        </span>
                                    </div>
                                    <div class="metric-row">
                                        <span class="metric-label">المسار:</span>
                                        <span class="metric-value">{{ $item['path'] }}</span>
                                    </div>
                                </div>

                                <a href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer" class="view-image-link">
                                    عرض الصورة بالحجم الكامل ↗
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="summary-box">
                    <h3 class="summary-title">خلاصة المقارنة الفنية</h3>
                    <p class="summary-text">
                        توضح المقارنة أن الضغط القياسي بنفس الصيغة يقلل الحجم بنسبة ملحوظة دون فقدان تفاصيل ملحوظة، بينما صيغة <span class="summary-highlight">WebP</span> تحقق أعلى كفاءة ضغط بتوفير مساحة إضافية كبيرة مع الحفاظ على نقاء بصري يضاهي الأصل للعين المجردة.
                    </p>
                </div>
            </section>
        @endif
    </main>
</body>
</html>

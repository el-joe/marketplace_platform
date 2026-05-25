<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BlockTypeSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $types = [
            // hero
            ['code' => 'hero_slider', 'group' => 'hero', 'label_en' => 'Hero Slider', 'label_ar' => 'عارض الشرائح البطولي', 'icon' => 'ti-slideshow', 'description' => 'Full-width image/text slider'],
            ['code' => 'countdown_deal', 'group' => 'hero', 'label_en' => 'Countdown Deal', 'label_ar' => 'عرض العد التنازلي', 'icon' => 'ti-clock-countdown', 'description' => 'Countdown + product strip', 'max_per_page' => 1],
            ['code' => 'video_banner', 'group' => 'hero', 'label_en' => 'Video Banner', 'label_ar' => 'بانر فيديو', 'icon' => 'ti-video', 'description' => 'Autoplay video with CTA overlay'],
            ['code' => 'occasion_banner', 'group' => 'hero', 'label_en' => 'Occasion Banner', 'label_ar' => 'بانر المناسبة', 'icon' => 'ti-calendar-event', 'description' => 'Seasonal/event themed hero'],

            // products
            ['code' => 'product_row', 'group' => 'products', 'label_en' => 'Product Row', 'label_ar' => 'صف المنتجات', 'icon' => 'ti-layout-columns', 'description' => 'Horizontal product grid with title'],
            ['code' => 'flash_sale', 'group' => 'products', 'label_en' => 'Flash Sale', 'label_ar' => 'تخفيضات سريعة', 'icon' => 'ti-bolt', 'description' => 'Flash sale strip with countdown'],
            ['code' => 'deal_of_day', 'group' => 'products', 'label_en' => 'Deal of the Day', 'label_ar' => 'عرض اليوم', 'icon' => 'ti-star', 'description' => 'Single featured deal with timer', 'max_per_page' => 1],
            ['code' => 'recently_viewed', 'group' => 'products', 'label_en' => 'Recently Viewed', 'label_ar' => 'شوهد مؤخراً', 'icon' => 'ti-history', 'description' => 'Personalized viewed history'],
            ['code' => 'top_rated', 'group' => 'products', 'label_en' => 'Top Rated', 'label_ar' => 'الأعلى تقييماً', 'icon' => 'ti-award', 'description' => 'High-rated products'],
            ['code' => 'new_arrivals', 'group' => 'products', 'label_en' => 'New Arrivals', 'label_ar' => 'وصل حديثاً', 'icon' => 'ti-sparkles', 'description' => 'Freshly listed products'],
            ['code' => 'seller_spotlight', 'group' => 'products', 'label_en' => 'Seller Spotlight', 'label_ar' => 'تسليط الضوء على البائع', 'icon' => 'ti-user-star', 'description' => 'Featured vendor with their products'],
            ['code' => 'comparison_table', 'group' => 'products', 'label_en' => 'Comparison Table', 'label_ar' => 'جدول المقارنة', 'icon' => 'ti-table', 'description' => 'Side-by-side product comparison'],

            // ads_banners
            ['code' => 'ad_images_2col', 'group' => 'ads_banners', 'label_en' => 'Ad Images 2-Column', 'label_ar' => 'صور إعلانية عمودان', 'icon' => 'ti-layout-2-columns', 'description' => 'Two ad images side by side'],
            ['code' => 'ad_images_3col', 'group' => 'ads_banners', 'label_en' => 'Ad Images 3-Column', 'label_ar' => 'صور إعلانية ثلاثة أعمدة', 'icon' => 'ti-layout-columns-3', 'description' => 'Three ad images in a row'],
            ['code' => 'ad_images_4col', 'group' => 'ads_banners', 'label_en' => 'Ad Images 4-Column', 'label_ar' => 'صور إعلانية أربعة أعمدة', 'icon' => 'ti-layout-grid', 'description' => 'Four ad images in a row'],
            ['code' => 'full_banner', 'group' => 'ads_banners', 'label_en' => 'Full Banner', 'label_ar' => 'بانر كامل العرض', 'icon' => 'ti-photo', 'description' => 'Single full-width banner image'],
            ['code' => 'split_banner', 'group' => 'ads_banners', 'label_en' => 'Split Banner', 'label_ar' => 'بانر مقسم', 'icon' => 'ti-layout-sidebar', 'description' => 'One large left + stacked small right'],
            ['code' => 'sponsored_products', 'group' => 'ads_banners', 'label_en' => 'Sponsored Products', 'label_ar' => 'منتجات مدفوعة', 'icon' => 'ti-ad', 'description' => 'CPC/CPM auto-filled sponsored slots'],
            ['code' => 'paid_banner', 'group' => 'ads_banners', 'label_en' => 'Paid Banner Slot', 'label_ar' => 'بانر مدفوع', 'icon' => 'ti-currency-dollar', 'description' => 'Sold banner slot (booking-based)', 'requires_permission' => 'manage_paid_banners'],

            // discovery
            ['code' => 'category_pills', 'group' => 'discovery', 'label_en' => 'Category Pills', 'label_ar' => 'فقاعات الفئات', 'icon' => 'ti-category', 'description' => 'Scrollable category bubbles'],
            ['code' => 'brand_strip', 'group' => 'discovery', 'label_en' => 'Brand Strip', 'label_ar' => 'شريط العلامات التجارية', 'icon' => 'ti-brand-chrome', 'description' => 'Brand logo carousel'],
            ['code' => 'search_trends', 'group' => 'discovery', 'label_en' => 'Search Trends', 'label_ar' => 'توجهات البحث', 'icon' => 'ti-trending-up', 'description' => 'Trending search keywords'],
            ['code' => 'geo_recommendations', 'group' => 'discovery', 'label_en' => 'Geo Recommendations', 'label_ar' => 'توصيات جغرافية', 'icon' => 'ti-map-pin', 'description' => 'Popular in your city'],

            // engagement
            ['code' => 'countdown_timer', 'group' => 'engagement', 'label_en' => 'Countdown Timer', 'label_ar' => 'مؤقت العد التنازلي', 'icon' => 'ti-clock', 'description' => 'Standalone timer bar'],
            ['code' => 'how_it_works', 'group' => 'engagement', 'label_en' => 'How It Works', 'label_ar' => 'كيف يعمل', 'icon' => 'ti-list-numbers', 'description' => 'Step-by-step numbered guide'],
            ['code' => 'loyalty_banner', 'group' => 'engagement', 'label_en' => 'Loyalty Banner', 'label_ar' => 'بانر الولاء', 'icon' => 'ti-crown', 'description' => 'VIP/loyalty program upsell'],
            ['code' => 'loyalty_progress', 'group' => 'engagement', 'label_en' => 'Loyalty Progress', 'label_ar' => 'تقدم الولاء', 'icon' => 'ti-progress', 'description' => 'Personalized VIP tier progress'],
            ['code' => 'poll_widget', 'group' => 'engagement', 'label_en' => 'Poll Widget', 'label_ar' => 'عنصر التصويت', 'icon' => 'ti-chart-bar', 'description' => 'Voting poll with results'],
            ['code' => 'review_highlights', 'group' => 'engagement', 'label_en' => 'Review Highlights', 'label_ar' => 'أبرز التقييمات', 'icon' => 'ti-star-half', 'description' => 'Featured customer reviews'],
            ['code' => 'newsletter_signup', 'group' => 'engagement', 'label_en' => 'Newsletter Signup', 'label_ar' => 'الاشتراك في النشرة', 'icon' => 'ti-mail', 'description' => 'Email capture form'],
            ['code' => 'app_download_banner', 'group' => 'engagement', 'label_en' => 'App Download Banner', 'label_ar' => 'بانر تحميل التطبيق', 'icon' => 'ti-device-mobile', 'description' => 'iOS/Android app install nudge'],
            ['code' => 'instagram_feed', 'group' => 'engagement', 'label_en' => 'Instagram Feed', 'label_ar' => 'خلاصة إنستجرام', 'icon' => 'ti-brand-instagram', 'description' => 'UGC social feed embed'],
            ['code' => 'text_block', 'group' => 'engagement', 'label_en' => 'Text Block', 'label_ar' => 'كتلة نصية', 'icon' => 'ti-file-text', 'description' => 'Free HTML/text content'],
            ['code' => 'divider', 'group' => 'engagement', 'label_en' => 'Divider / Spacer', 'label_ar' => 'فاصل / مسافة', 'icon' => 'ti-separator', 'description' => 'Visual separator or spacer'],
        ];

        $sortOrder = 0;
        $rows = [];
        foreach ($types as $type) {
            $rows[] = [
                'id' => (string) Str::uuid(),
                'code' => $type['code'],
                'label_en' => $type['label_en'],
                'label_ar' => $type['label_ar'],
                'group' => $type['group'],
                'icon' => $type['icon'],
                'description' => $type['description'] ?? null,
                'config_schema' => '{}',
                'default_config' => '{}',
                'is_active' => true,
                'requires_permission' => $type['requires_permission'] ?? null,
                'max_per_page' => $type['max_per_page'] ?? null,
                'sort_order' => $sortOrder++,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('block_types')->upsert($rows, ['code'], [
            'label_en',
            'label_ar',
            'group',
            'icon',
            'description',
            'requires_permission',
            'max_per_page',
            'sort_order',
            'updated_at',
        ]);
    }
}

<?php

namespace App\Support\AdSupport;

/**
 * Static content catalog for the noon-ads-style Knowledge Hub (/{country}/adsupport/*).
 *
 * Mirrors adsupport.noon.com: collections are keyed by their numeric id (the
 * same id used in the noon URL segment "{id}-{slug}"), articles the same way.
 * The slug half of the segment is cosmetic — lookups only match the id, same
 * as the real Intercom-powered site.
 */
class AdSupportCatalog
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function collections(): array
    {
        return [
            '11857798' => [
                'slug' => 'getting-started',
                'name' => 'Getting Started',
                'description' => 'Advertising on noon can significantly boost your brand’s visibility and help you connect with high-intent customers actively searching for products like yours.',
                'icon' => 'https://intercom.help/noon-adsupport/assets/svg/icon:other-rocket-launch',
                'article_count' => 3,
                'subcollections' => [
                    '11895262' => [
                        'slug' => 'overview',
                        'name' => 'Overview',
                        'articles' => ['10681916', '10682229', '11829862'],
                    ],
                ],
            ],
            '11857792' => [
                'slug' => 'quick-guides',
                'name' => 'Quick Guides',
                'description' => 'Find comprehensive guides that will walk you through the process of creating and launching ad campaigns.',
                'icon' => 'https://intercom.help/noon-adsupport/assets/svg/icon:content-book-open',
                'article_count' => 3,
                'subcollections' => [],
            ],
            '11857799' => [
                'slug' => 'release-notes',
                'name' => 'Release Notes',
                'description' => 'Stay informed about the latest noon ads updates.',
                'icon' => 'https://intercom.help/noon-adsupport/assets/svg/icon:sft-megaphone',
                'article_count' => 10,
                'subcollections' => [],
            ],
            '11857795' => [
                'slug' => 'manage-campaigns',
                'name' => 'Manage Campaigns',
                'description' => 'Deep dive into campaign creation steps',
                'icon' => 'https://intercom.help/noon-adsupport/assets/svg/icon:sft-squares-plus',
                'article_count' => 6,
                'subcollections' => [],
            ],
            '11857796' => [
                'slug' => 'optimize-campaigns',
                'name' => 'Optimize Campaigns',
                'description' => 'Improve your campaigns\' performance by adjusting the necessary parameters to achieve your advertising goals.',
                'icon' => 'https://intercom.help/noon-adsupport/assets/svg/icon:sft-adjustments-horizontal',
                'article_count' => 11,
                'subcollections' => [],
            ],
            '11857801' => [
                'slug' => 'billing-payment',
                'name' => 'Billing & Payment',
                'description' => 'Effectively manage invoices and payments to ensure a smooth advertising experience.',
                'icon' => 'https://intercom.help/noon-adsupport/assets/svg/icon:bizz-fin-currency-dollar',
                'article_count' => 1,
                'subcollections' => [],
            ],
            '11857800' => [
                'slug' => 'account-management',
                'name' => 'Account Management',
                'description' => 'Easily manage your account settings to customize your advertising experience',
                'icon' => 'https://intercom.help/noon-adsupport/assets/svg/icon:people-chat-gets-user-circle',
                'article_count' => 4,
                'subcollections' => [],
            ],
            '11857802' => [
                'slug' => 'advertising-resources',
                'name' => 'Advertising Resources',
                'description' => 'Prepare for retail events and get inspired by advertising success stories.',
                'icon' => 'https://intercom.help/noon-adsupport/assets/svg/icon:ff-folder-open',
                'article_count' => 7,
                'subcollections' => [],
            ],
            '11857803' => [
                'slug' => 'faqs',
                'name' => 'FAQs',
                'description' => '',
                'icon' => 'https://intercom.help/noon-adsupport/assets/svg/icon:arr-sym-question-mark-circle',
                'article_count' => 8,
                'subcollections' => [],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function articles(): array
    {
        return [
            '10681916' => [
                'title' => 'What Are Self-Serve Ad Campaigns',
                'collection_id' => '11857798',
                'subcollection_id' => '11895262',
                'updated_label' => 'July 23, 2025',
                'updated_datetime' => '2025-07-23T14:29:16Z',
                'toc' => [
                    ['id' => 'h_64477c7a59', 'label' => 'Key Benefits'],
                    ['id' => 'h_78c3346bf3', 'label' => 'How Do They Work'],
                    ['id' => 'h_eb7fd47359', 'label' => 'Comparison Between Ad Types'],
                    ['id' => 'h_e7db1e84b4', 'label' => 'Product Ads'],
                    ['id' => 'h_0ffccff13d', 'label' => 'Brand Ads'],
                    ['id' => 'h_cf8bd96569', 'label' => 'Display Ads'],
                ],
                'related_articles' => ['10682293', '10682315', '10696604', '10714264', '10717610'],
                'body_view' => 'portal.adsupport.articles.what-are-self-serve-ad-campaigns',
            ],
            '10682229' => [
                'title' => 'How to Navigate the Campaigns Page',
                'collection_id' => '11857798',
                'subcollection_id' => '11895262',
                'updated_label' => 'July 23, 2025',
                'updated_datetime' => '2025-07-23T12:12:40Z',
                'toc' => [],
                'related_articles' => ['10681916', '11829862'],
                'stub' => true,
            ],
            '11829862' => [
                'title' => 'How to Access Ad Manager',
                'collection_id' => '11857798',
                'subcollection_id' => '11895262',
                'updated_label' => 'July 23, 2025',
                'updated_datetime' => '2025-07-23T10:05:30Z',
                'toc' => [],
                'related_articles' => ['10681916', '10682229'],
                'stub' => true,
            ],
            '10682293' => ['title' => 'How to Create a Product Ads Campaign', 'stub' => true],
            '10682315' => ['title' => 'How to Scale Your Ads with noon ads', 'stub' => true],
            '10696604' => ['title' => 'How to Create a Brand Ads Campaign', 'stub' => true],
            '10696613' => ['title' => 'How to Create a Display Ads Campaign', 'stub' => true],
            '10714264' => ['title' => 'What Is Campaign Bidding', 'stub' => true],
            '10714127' => ['title' => 'Campaign Targeting', 'stub' => true],
            '10714222' => ['title' => 'Selecting SKUs to Advertise', 'stub' => true],
            '10717610' => ['title' => 'What is the Campaign Overview Report', 'stub' => true],
            '15027796' => ['title' => 'What Is the Seller Accelerator Program', 'stub' => true],
        ];
    }

    public static function findCollection(string $segment): ?array
    {
        $id = static::extractId($segment);
        $collection = static::collections()[$id] ?? null;

        return $collection ? $collection + ['id' => $id] : null;
    }

    public static function findArticle(string $segment): ?array
    {
        $id = static::extractId($segment);
        $article = static::articles()[$id] ?? null;

        if ($article === null) {
            return null;
        }

        return $article + [
            'id' => $id,
            'collection_id' => null,
            'subcollection_id' => null,
            'updated_label' => 'Recently updated',
            'updated_datetime' => now()->toIso8601String(),
            'toc' => [],
            'related_articles' => [],
        ];
    }

    public static function segmentFor(string $id, string $slug): string
    {
        return "{$id}-{$slug}";
    }

    protected static function extractId(string $segment): string
    {
        preg_match('/^\d+/', $segment, $matches);

        return $matches[0] ?? $segment;
    }
}

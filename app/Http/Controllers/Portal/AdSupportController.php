<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Support\AdSupport\AdSupportCatalog;
use Illuminate\View\View;

class AdSupportController extends Controller
{
    public function index(string $country): View
    {
        $collections = AdSupportCatalog::collections();

        return view('portal.adsupport.index', compact('country', 'collections'));
    }

    public function collection(string $country, string $collection): View
    {
        $data = AdSupportCatalog::findCollection($collection);

        abort_if($data === null, 404);

        $articles = AdSupportCatalog::articles();

        return view('portal.adsupport.collection', [
            'country' => $country,
            'collection' => $data,
            'articles' => $articles,
        ]);
    }

    public function article(string $country, string $article): View
    {
        $data = AdSupportCatalog::findArticle($article);

        abort_if($data === null, 404);

        $collections = AdSupportCatalog::collections();
        $allArticles = AdSupportCatalog::articles();

        return view('portal.adsupport.article', [
            'country' => $country,
            'article' => $data,
            'collections' => $collections,
            'allArticles' => $allArticles,
        ]);
    }
}

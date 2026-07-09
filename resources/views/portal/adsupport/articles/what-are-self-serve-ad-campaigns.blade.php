@php
    use App\Support\AdSupport\AdSupportCatalog;

    $biddingUrl = route('portal.adsupport.articles.show', ['country' => $country, 'article' => AdSupportCatalog::segmentFor('10714264', 'campaign-bidding')]);
    $targetingUrl = route('portal.adsupport.articles.show', ['country' => $country, 'article' => AdSupportCatalog::segmentFor('10714127', 'campaign-targeting')]);
    $skusUrl = route('portal.adsupport.articles.show', ['country' => $country, 'article' => AdSupportCatalog::segmentFor('10714222', 'selecting-skus-to-advertise')]);
    $productCampaignUrl = route('portal.adsupport.articles.show', ['country' => $country, 'article' => AdSupportCatalog::segmentFor('10682293', 'create-a-product-ads-campaign')]);
    $brandCampaignUrl = route('portal.adsupport.articles.show', ['country' => $country, 'article' => AdSupportCatalog::segmentFor('10696604', 'create-a-brand-ads-campaign')]);
    $displayCampaignUrl = route('portal.adsupport.articles.show', ['country' => $country, 'article' => AdSupportCatalog::segmentFor('10696613', 'create-a-display-ads-campaign')]);
    $quickGuidesUrl = route('portal.adsupport.collections.show', ['country' => $country, 'collection' => AdSupportCatalog::segmentFor('11857792', 'quick-guides')]);
@endphp

<div class="mb-4 text-justify leading-relaxed">
    <p>Self-serve ads allow sellers and brands to create and manage ad campaigns, offering flexibility, control, and cost-effectiveness. Whether you are a small business or a growing enterprise, noon&rsquo;s self-serve ad platform provides tools to promote your products, increase visibility, and drive sales.</p>
</div>

<h2 id="h_64477c7a59" class="mb-4 mt-8 text-xl font-bold text-black">Key Benefits</h2>
<ol class="mb-4 list-decimal space-y-2 ps-6 leading-relaxed">
    <li><b>Flexibility:</b> Start, pause, or adjust your campaigns at any time</li>
    <li><b>Budget Control:</b> Set daily budgets and <a href="{{ $biddingUrl }}" class="text-black underline hover:opacity-70">bids</a> that match your advertising budget</li>
    <li><b>Performance Tracking:</b> Monitor campaigns' performance and optimize them based on data</li>
    <li><b>Targeted Advertising:</b> Reach customers searching for products or browsing relevant categories by using <a href="{{ $targetingUrl }}" class="text-black underline hover:opacity-70">auto or manual targeting across categories and keywords</a></li>
</ol>

<h2 id="h_78c3346bf3" class="mb-4 mt-8 text-xl font-bold text-black">How Do They Work</h2>
<div class="mb-4 text-justify leading-relaxed">
    <p>Self-serve ads operate on an open-auction model, where advertisers compete for targets/placements by setting bids. The ad with the highest ad rank, determined by the bid amount and the relevance score, wins the auction and appears.</p>
</div>
<ul class="mb-4 list-disc space-y-2 ps-6 leading-relaxed">
    <li><b><a href="{{ $biddingUrl }}" class="text-black underline hover:opacity-70">Bid Amount</a>:</b> The amount you're willing to pay per click or thousand impressions, depending on the pricing model</li>
    <li><b>Relevance Score</b>: A rating that measures how well your chosen targets (keywords and categories), along with the title and content of your advertised products' detail page, match user intent and influence the likelihood that customers will engage with your advertised product listings and purchase your <a href="{{ $skusUrl }}" class="text-black underline hover:opacity-70">advertised products</a></li>
</ul>

<hr class="my-6 border-0 border-t border-solid border-[#e6e6e6]">

<div class="mb-4 leading-relaxed">
    <p><b>💡Tip</b></p>
</div>
<ul class="mb-4 list-disc space-y-2 ps-6 leading-relaxed">
    <li>A well-optimized product detail page can improve your ad performance</li>
    <li>Ensure that your <a href="https://support.noon.partners/portal/en/kb/articles/title-requirements-and-rejection-reasons-for-the-seller-sku" rel="nofollow noopener noreferrer" target="_blank" class="text-black underline hover:opacity-70">product title</a> is relevant to the keywords that you are using</li>
    <li>Update your <a href="https://support.noon.partners/portal/en/kb/articles/feature-bullets-product-highlights-and-description-for-the-seller-sku" rel="nofollow noopener noreferrer" target="_blank" class="text-black underline hover:opacity-70">product description</a></li>
</ul>

<hr class="my-6 border-0 border-t border-solid border-[#e6e6e6]">

<p class="mb-4 leading-relaxed">The table below explains the Ad ranking process:</p>

<div class="mb-6 overflow-x-auto">
    <table class="w-full min-w-[560px] border-collapse text-start">
        <tbody>
            <tr>
                <td class="border border-solid border-[#cccccc] bg-[#e8e8e880] p-2"><b>Ad (Product x Target)</b></td>
                <td class="border border-solid border-[#cccccc] bg-[#e8e8e880] p-2"><b>Bid</b></td>
                <td class="border border-solid border-[#cccccc] bg-[#e8e8e880] p-2"><b>Relevance Score</b></td>
                <td class="border border-solid border-[#cccccc] bg-[#e8e8e880] p-2"><b>Total Score (Bid x Relevance)</b></td>
                <td class="border border-solid border-[#cccccc] bg-[#e8e8e880] p-2"><b>Ad Ranking</b></td>
            </tr>
            <tr>
                <td class="border border-solid border-[#cccccc] bg-[#feedaf80] p-2">Ad 1</td>
                <td class="border border-solid border-[#cccccc] bg-[#feedaf80] p-2">0.8</td>
                <td class="border border-solid border-[#cccccc] bg-[#feedaf80] p-2">0.9</td>
                <td class="border border-solid border-[#cccccc] bg-[#feedaf80] p-2">0.72</td>
                <td class="border border-solid border-[#cccccc] bg-[#feedaf80] p-2">1</td>
            </tr>
            <tr>
                <td class="border border-solid border-[#cccccc] p-2">Ad 2</td>
                <td class="border border-solid border-[#cccccc] p-2">1.0</td>
                <td class="border border-solid border-[#cccccc] p-2">0.6</td>
                <td class="border border-solid border-[#cccccc] p-2">0.6</td>
                <td class="border border-solid border-[#cccccc] p-2">2</td>
            </tr>
            <tr>
                <td class="border border-solid border-[#cccccc] p-2">Ad 3</td>
                <td class="border border-solid border-[#cccccc] p-2">0.5</td>
                <td class="border border-solid border-[#cccccc] p-2">0.5</td>
                <td class="border border-solid border-[#cccccc] p-2">0.25</td>
                <td class="border border-solid border-[#cccccc] p-2">3</td>
            </tr>
        </tbody>
    </table>
</div>

<h1 id="h_eb7fd47359" class="mb-4 mt-8 text-2xl font-bold text-black">Comparison Between Ad Types</h1>
<p class="mb-4 text-justify leading-relaxed">noon ads offers three self-serve ad solutions &mdash; Product Ads, Brand Ads, and Display Ads &mdash; each having unique objectives and catering to different advertising needs.</p>

<h2 id="h_e7db1e84b4" class="mb-4 mt-8 text-xl font-bold text-black">Product Ads</h2>
<p class="mb-4 text-justify leading-relaxed"><a href="{{ $productCampaignUrl }}" class="text-black underline hover:opacity-70">Product Ads</a> are designed to drive sales and increase individual product visibility by appearing in key placements (see image below).</p>

<div class="mb-4 rounded-lg p-4" style="background-color:#e3e7fa80;border:1px solid #334bfa33">
    <p><b>Example</b>: A seller promoting an air fryer can have their ad appear at the top of relevant search results when a customer searches for "air fryer".</p>
</div>
<p class="mb-4 text-justify leading-relaxed">This placement targets high-intent shoppers, increasing the likelihood of a purchase.</p>

<div class="mb-4">
    <a href="https://downloads.intercomcdn.com/i/o/yba8j1xj/1443729459/0536dfc1be3ce4d82369cd54635a/image.png?expires=1783600200&signature=4469ff38c94198cb363e3d84c752e5a9be212971a9336ea1933af13d3cf90574&req=dSQjFc58lIVaUPMW1HO4zcJDK6Ow1tXJakJakIOvhoZVh%2FPir0mziF61RLcp%0AAso4li6pajaZ3lniAd8%3D%0A" target="_blank" rel="noreferrer nofollow noopener">
        <img src="https://downloads.intercomcdn.com/i/o/yba8j1xj/1443729459/0536dfc1be3ce4d82369cd54635a/image.png?expires=1783600200&signature=4469ff38c94198cb363e3d84c752e5a9be212971a9336ea1933af13d3cf90574&req=dSQjFc58lIVaUPMW1HO4zcJDK6Ow1tXJakJakIOvhoZVh%2FPir0mziF61RLcp%0AAso4li6pajaZ3lniAd8%3D%0A"
             alt="" width="996" height="472" loading="lazy" class="h-auto max-w-full rounded-lg border border-solid border-[#e6e6e6]">
    </a>
</div>

<h2 id="h_0ffccff13d" class="mb-4 mt-8 text-xl font-bold text-black">Brand Ads</h2>
<p class="mb-4 text-justify leading-relaxed"><a href="{{ $brandCampaignUrl }}" class="text-black underline hover:opacity-70">Brand Ads</a> focus on building brand awareness and enhancing brand discovery by showcasing a brand&rsquo;s logo, custom headline, and multiple products in key placements (see image below). Available in banner and video formats, these ads help brands capture customers' attention and drive engagement.</p>

<div class="mb-4 rounded-lg p-4" style="background-color:#e3e7fa80;border:1px solid #334bfa33">
    <p><b>Example:</b> A sportswear brand can use Brand Ads to display shoes, apparel, and accessories, encouraging shoppers to explore its product range.</p>
</div>

<div class="mb-4">
    <a href="https://downloads.intercomcdn.com/i/o/yba8j1xj/1503326406/b12b587592c7c8d64f81c064bcc5/image+%2866%29.png?expires=1783600200&signature=58a8343b492509d8fee31c29a89387ae20930d8b250d8819edb265ef44c0b92c&req=dSUnFcp8m4VfX%2FMW1HO4zdtxNBzQbA5P9SwSUfzSFMr5W%2BKRTBvPHgGr8U4d%0AvC7gj%2BF4l6o1Df%2BOs6A%3D%0A" target="_blank" rel="noreferrer nofollow noopener">
        <img src="https://downloads.intercomcdn.com/i/o/yba8j1xj/1503326406/b12b587592c7c8d64f81c064bcc5/image+%2866%29.png?expires=1783600200&signature=58a8343b492509d8fee31c29a89387ae20930d8b250d8819edb265ef44c0b92c&req=dSUnFcp8m4VfX%2FMW1HO4zdtxNBzQbA5P9SwSUfzSFMr5W%2BKRTBvPHgGr8U4d%0AvC7gj%2BF4l6o1Df%2BOs6A%3D%0A"
             alt="" width="1012" height="506" loading="lazy" class="h-auto max-w-full rounded-lg border border-solid border-[#e6e6e6]">
    </a>
</div>

<h2 id="h_cf8bd96569" class="mb-4 mt-8 text-xl font-bold text-black">Display Ads</h2>
<p class="mb-4 text-justify leading-relaxed"><a href="{{ $displayCampaignUrl }}" class="text-black underline hover:opacity-70">Display Ads</a> are static graphic banners ideal for boosting brand awareness and promoting campaigns such as seasonal sales or new product launches. These ads are placed in high-visibility locations (see image below).</p>

<div class="mb-4 rounded-lg p-4" style="background-color:#e3e7fa80;border:1px solid #334bfa33">
    <p><b>Example:</b> A skincare brand running a clearance sale can use Display Ads on the homepage to capture attention and redirect traffic to the sale page.</p>
</div>

<div class="mb-4 text-center">
    <a href="https://downloads.intercomcdn.com/i/o/yba8j1xj/1401228719/5ff955af1b98b0e81c3a91eb23b0/Screen+Shot+2025-02-28+at+4_45_07+PM.png?expires=1783600200&signature=2ef14dbc514275f9f819750669f0b672a4556de2200632e8b05d852c544465a2&req=dSQnF8t8lYZeUPMW1HO4zVK54JyCE4dMnr09D9leO40%2FCgdkzBV55veGq7Zm%0A544RV6EUKZQIFvBl6WU%3D%0A" target="_blank" rel="noreferrer nofollow noopener">
        <img src="https://downloads.intercomcdn.com/i/o/yba8j1xj/1401228719/5ff955af1b98b0e81c3a91eb23b0/Screen+Shot+2025-02-28+at+4_45_07+PM.png?expires=1783600200&signature=2ef14dbc514275f9f819750669f0b672a4556de2200632e8b05d852c544465a2&req=dSQnF8t8lYZeUPMW1HO4zVK54JyCE4dMnr09D9leO40%2FCgdkzBV55veGq7Zm%0A544RV6EUKZQIFvBl6WU%3D%0A"
             alt="" width="2040" height="1036" loading="lazy" class="h-auto max-w-full rounded-lg border border-solid border-[#e6e6e6]">
    </a>
</div>

<p class="mb-4 text-justify leading-relaxed">If you are quick-starting your advertising journey on noon, make sure to head to our <a href="{{ $quickGuidesUrl }}" class="text-black underline hover:opacity-70">campaign creation guides</a>.</p>

<hr class="my-6 border-0 border-t border-solid border-[#e6e6e6]">

<div class="mb-1 text-center leading-relaxed">
    <p><b>Need more help?</b> Email us at <a href="mailto:adsupport@noon.com" rel="nofollow noopener noreferrer" target="_blank" class="text-black underline hover:opacity-70">adsupport@noon.com</a></p>
</div>
<p class="text-center leading-relaxed">Our dedicated support team will answer all your questions</p>

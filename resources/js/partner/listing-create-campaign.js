/**
 * partner/listing-create-campaign.js
 * Campaign section behavior for the listing create page (partner/listings/create.blade.php)
 */

document.addEventListener('DOMContentLoaded', function () {
    const section = document.getElementById('campaign-section');
    if (!section) return;

    const enabledCheckbox = document.getElementById('campaign-enabled-checkbox');
    const notFbnWarning = document.getElementById('campaign-not-fbn-warning');
    const details = document.getElementById('campaign-details');
    const marketerSelect = document.getElementById('marketer_vendor_ids');
    const commissionTypeSelect = document.getElementById('commission-type-select');
    const tieredSection = document.getElementById('tiered-rules-section');
    const tieredList = document.getElementById('tiered-rules-list');
    const addTierBtn = document.getElementById('add-tier-btn');
    const totalSamplesEl = document.getElementById('total-samples-value');
    const sampleBreakdownEl = document.getElementById('sample-breakdown-text');
    const fmSelect = document.querySelector('select[name="fulfillment_model"]');

    const state = {
        influencerCount: 0,
        affiliateCount: 0,
        influencerSampleQty: 0,
        affiliateSampleQty: 0,
        platformSampleQty: 0,
        samplesLoaded: false,
        tieredRules: [],
    };

    function isFbn() {
        return fmSelect ? fmSelect.value === 'fbn' : false;
    }

    function totalSamples() {
        return (state.influencerCount * state.influencerSampleQty)
             + (state.affiliateCount * state.affiliateSampleQty)
             + state.platformSampleQty;
    }

    function sampleBreakdown() {
        return "";
        if (!state.samplesLoaded) return 'سيتم حساب العينات بعد اختيار المنتج';
        const parts = [];
        if (state.influencerCount > 0)
            parts.push(`${state.influencerCount} إنفلوينسر × ${state.influencerSampleQty}`);
        if (state.affiliateCount > 0)
            parts.push(`${state.affiliateCount} أفيلييت × ${state.affiliateSampleQty}`);
        if (state.platformSampleQty > 0)
            parts.push(`منصة ${state.platformSampleQty}`);
        return parts.length
            ? `(${parts.join(' + ')}) = ${totalSamples()} عينة`
            : `${totalSamples()} عينة`;
    }

    function renderSamples() {
        if (totalSamplesEl) totalSamplesEl.textContent = totalSamples();
        if (sampleBreakdownEl) sampleBreakdownEl.textContent = sampleBreakdown();
    }

    function renderVisibility() {
        const enabled = !!(enabledCheckbox && enabledCheckbox.checked);
        const fbn = isFbn();
        notFbnWarning?.classList.toggle('hidden', fbn);
        details?.classList.toggle('hidden', !(enabled && fbn));
        if (enabled && fbn) window.initSelect2 && window.initSelect2();
    }

    function renderTieredVisibility() {
        tieredSection?.classList.toggle('hidden', commissionTypeSelect?.value !== 'tiered');
    }

    function renderTieredRules() {
        if (!tieredList) return;
        tieredList.innerHTML = '';
        state.tieredRules.forEach((rule, i) => {
            const row = document.createElement('div');
            row.className = 'flex gap-2 items-center';
            row.innerHTML = `
                <input type="number" name="tiered_rules[${i}][from_sale_number]"
                       value="${rule.from_sale_number}"
                       placeholder="رقم البيعة (مثال: 10)"
                       class="w-1/2 border border-gray-200 rounded-xl px-3 py-2 text-sm">
                <input type="number" name="tiered_rules[${i}][commission_amount]"
                       value="${rule.commission_amount}"
                       placeholder="مبلغ الكوميشن"
                       class="w-1/2 border border-gray-200 rounded-xl px-3 py-2 text-sm">
                <button type="button" class="text-red-500 hover:text-red-700" data-remove-tier="${i}">
                    <i class="fas fa-times"></i>
                </button>
            `;
            const [fromInput, amountInput] = row.querySelectorAll('input');
            fromInput.addEventListener('input', (e) => { rule.from_sale_number = e.target.value; });
            amountInput.addEventListener('input', (e) => { rule.commission_amount = e.target.value; });
            row.querySelector('[data-remove-tier]').addEventListener('click', () => {
                state.tieredRules.splice(i, 1);
                renderTieredRules();
            });
            tieredList.appendChild(row);
        });
    }

    async function fetchCategorySamples(productId) {
        const url = window.LISTINGS_CREATE?.categorySamplesUrl;
        if (!productId || !url) return;
        try {
            const res = await fetch(`${url}?product_id=${productId}`, {
                headers: { 'Accept': 'application/json' },
            });
            const data = await res.json();
            state.influencerSampleQty = data.influencer ?? 0;
            state.affiliateSampleQty = data.affiliate ?? 0;
            state.platformSampleQty = data.platform ?? 0;
            state.samplesLoaded = true;
            renderSamples();
        } catch (e) {
            console.error('Failed to fetch category samples', e);
        }
    }

    function updateMarketerCounts(event) {
        const select = event.target;
        state.influencerCount = 0;
        state.affiliateCount = 0;
        Array.from(select.selectedOptions || []).forEach(opt => {
            if (opt.dataset.type === 'influencer') state.influencerCount++;
            else if (opt.dataset.type === 'affiliate') state.affiliateCount++;
        });
        renderSamples();
    }

    enabledCheckbox?.addEventListener('change', renderVisibility);
    fmSelect?.addEventListener('change', renderVisibility);
    commissionTypeSelect?.addEventListener('change', renderTieredVisibility);
    marketerSelect?.addEventListener('change', updateMarketerCounts);
    // select2 (data-select2-init) fires jQuery 'change', not native DOM 'change'
    if (window.jQuery && marketerSelect) {
        jQuery(marketerSelect).on('change', updateMarketerCounts);
    }
    addTierBtn?.addEventListener('click', () => {
        state.tieredRules.push({ from_sale_number: '', commission_amount: '' });
        renderTieredRules();
    });
    document.addEventListener('listing:product-selected', (e) => {
        fetchCategorySamples(e.detail?.productId);
    });

    renderVisibility();
    renderTieredVisibility();
    renderSamples();
});

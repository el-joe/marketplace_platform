<?php

namespace App\Providers;

use App\View\Components\Form\AsyncSelect;
use App\View\Components\Form\FileUpload;
use App\View\Components\Form\Input;
use App\View\Components\Form\PriceInput;
use App\View\Components\Form\RichEditor;
use App\View\Components\Form\Select;
use App\Events\SubOrderPlaced;
use App\Listeners\InvalidateVendorDashboardCache;
use App\Listeners\RecordMarketerConversion;
use App\Services\Payment\PaymentGatewayFactory;
use App\Services\Shipping\ShippingCarrierFactory;
use App\Models\Address;
use App\Models\SubOrder;
use App\Policies\AddressPolicy;
use App\Policies\SubOrderPolicy;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayFactory::class);
        $this->app->singleton(ShippingCarrierFactory::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Address::class, AddressPolicy::class);
        Gate::policy(SubOrder::class, SubOrderPolicy::class);

        Event::listen(SubOrderPlaced::class, InvalidateVendorDashboardCache::class);
        Event::listen(SubOrderPlaced::class, RecordMarketerConversion::class);

        // Hyphenated aliases used by admin Blade views (x-form-input etc.).
        Blade::component(Input::class, 'form-input');
        Blade::component(Select::class, 'form-select');
        Blade::component(PriceInput::class, 'form-price-input');
        Blade::component(AsyncSelect::class, 'form-async-select');
        Blade::component(RichEditor::class, 'form-rich-editor');
        Blade::component(FileUpload::class, 'form-file-upload');

        // x-file-upload (shorthand alias used in flash-sales view).
        Blade::component(FileUpload::class, 'file-upload');

        // Anonymous form components without a class-based counterpart.
        Blade::component('components.form.toggle', 'form-toggle');
        Blade::component('components.form.slug-input', 'form-slug-input');
        Blade::component('components.form.checkbox-group', 'form-checkbox-group');
        Blade::component('components.form.textarea', 'form-textarea');
        Blade::component('components.form.date-picker', 'form-date-picker');
        Blade::component('components.form.radio-group', 'form-radio-group');
    }
}

<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\ClassifiedInquiry;
use App\Models\ClassifiedListing;
use App\Models\ClassifiedListingAttachment;
use App\Models\ClassifiedListingMarketer;
use App\Models\Customer;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClassifiedListingService
{
    public function createDraft(array $data, Customer $customer): ClassifiedListing
    {
        return ClassifiedListing::create([
            'listing_number'          => 'CL-' . strtoupper(Str::random(8)),
            'customer_id'             => $customer->id,
            'classified_category_id'  => $data['classified_category_id'],
            'country_id'              => $data['country_id'],
            'city_id'                 => $data['city_id'] ?? null,
            'listing_purpose'         => $data['listing_purpose'],
            'title_en'                => $data['title_en'],
            'title_ar'                => $data['title_ar'],
            'description_en'          => $data['description_en'] ?? null,
            'description_ar'          => $data['description_ar'] ?? null,
            'price_cents'             => $data['price_cents'],
            'currency'                => $data['currency'],
            'price_negotiable'        => $data['price_negotiable'] ?? false,
            'attributes'              => $data['attributes'] ?? null,
            'latitude'                => $data['latitude'] ?? null,
            'longitude'               => $data['longitude'] ?? null,
            'sketch_file_path'        => $data['sketch_file_path'] ?? null,
            'status'                  => 'draft',
            'expires_at'              => $data['expires_at'] ?? null,
        ]);
    }

    public function attachContract(
        ClassifiedListing $listing,
        string $signatureName,
        string $ip
    ): ClassifiedListing {
        $category = $listing->classifiedCategory;
        $template = $category?->contractTemplate;

        if (! $template) {
            throw ValidationException::withMessages([
                'contract' => 'No contract template is configured for this category.',
            ]);
        }

        $listing->update([
            'contract_template_id'    => $template->id,
            'contract_accepted_at'    => now(),
            'contract_signature_data' => json_encode([
                'name'        => $signatureName,
                'ip'          => $ip,
                'accepted_at' => now()->toIso8601String(),
            ]),
            'status' => 'pending_review',
        ]);

        return $listing->fresh();
    }

    public function uploadAttachment(
        ClassifiedListing $listing,
        string $type,
        string $path
    ): ClassifiedListingAttachment {
        return ClassifiedListingAttachment::create([
            'classified_listing_id' => $listing->id,
            'attachment_type'       => $type,
            'file_path'             => $path,
            'status'                => 'pending',
        ]);
    }

    public function generateBarcode(ClassifiedListing $listing): string
    {
        $url = url('/classifieds/' . $listing->listing_number);

        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($url)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(300)
            ->margin(10)
            ->build();

        $filename = 'classified-qr/' . $listing->listing_number . '.png';
        Storage::put($filename, $result->getString());

        $listing->update(['barcode_path' => $filename]);

        return $filename;
    }

    public function assignMarketers(
        ClassifiedListing $listing,
        array $marketerIds,
        string $commissionType,
        float $commissionValue
    ): void {
        if (empty($marketerIds)) {
            ClassifiedListingMarketer::create([
                'classified_listing_id' => $listing->id,
                'marketer_id'           => null,
                'commission_type'       => $commissionType,
                'commission_value'      => $commissionValue,
            ]);
        } else {
            foreach ($marketerIds as $id) {
                ClassifiedListingMarketer::create([
                    'classified_listing_id' => $listing->id,
                    'marketer_id'           => $id,
                    'commission_type'       => $commissionType,
                    'commission_value'      => $commissionValue,
                ]);
            }
        }

        $listing->update(['marketer_promotion_enabled' => true]);
    }

    public function approve(ClassifiedListing $listing, Admin $admin): ClassifiedListing
    {
        if (! $listing->required_attachments_complete) {
            throw ValidationException::withMessages([
                'attachments' => 'All required attachments must be verified before approval.',
            ]);
        }

        $listing->update([
            'status'                => 'active',
            'approved_by_admin_id'  => $admin->id,
            'approved_at'           => now(),
        ]);

        dispatch(fn () => $this->generateBarcode($listing));

        return $listing->fresh();
    }

    public function reject(ClassifiedListing $listing, string $reason): ClassifiedListing
    {
        $listing->update([
            'status'           => 'rejected',
            'rejection_reason' => $reason,
        ]);

        return $listing->fresh();
    }

    public function recordInquiry(
        ClassifiedListing $listing,
        Customer $customer,
        array $data
    ): ClassifiedInquiry {
        return ClassifiedInquiry::create([
            'classified_listing_id' => $listing->id,
            'customer_id'           => $customer->id,
            'marketer_id'           => $data['marketer_id'] ?? null,
            'message'               => $data['message'] ?? null,
            'contact_phone'         => $data['contact_phone'] ?? null,
        ]);
    }
}

<?php

namespace App\Services\Vendor;

use App\Models\Vendor;
use App\Models\VendorDocument;
use Illuminate\Http\UploadedFile;

class DocumentService
{
    public function upload(Vendor $vendor, string $type, UploadedFile $file): VendorDocument
    {
        $path = $file->store("vendors/{$vendor->id}/documents", 'public');

        // Replace existing document of the same type, or create new.
        $doc = VendorDocument::firstOrNew([
            'vendor_id'     => $vendor->id,
            'document_type' => $type,
        ]);

        $doc->fill([
            'file_path'        => $path,
            'status'           => 'pending',
            'rejection_reason' => null,
        ])->save();

        return $doc->fresh();
    }
}

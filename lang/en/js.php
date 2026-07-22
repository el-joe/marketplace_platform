<?php

/*
|--------------------------------------------------------------------------
| JS-facing UI strings
|--------------------------------------------------------------------------
|
| This domain is dedicated to strings consumed by vanilla JS files under
| resources/js/**. It is emitted to the browser as `window.trans` by the
| portal layouts (see resources/views/layouts/*.blade.php) and read via the
| `t(path, vars?)` helper defined in resources/js/app.js.
|
| Keep this file organized by module so the JS `t('admin.vendors.foo')`
| calls map directly onto this nested structure.
|
*/

return [

    // ─── Strings shared across every portal (app.js, column-renderers.js) ───
    'shared' => [
        'yes'                       => 'Yes',
        'no'                        => 'No',
        'loading'                   => 'Loading…',
        'searching'                 => 'Searching…',
        'search_failed'             => 'Search failed.',
        'no_products_found'         => 'No products found.',
        'something_went_wrong'      => 'Something went wrong. Please try again.',
        'not_allowed_action'        => 'You are not allowed to perform this action.',
        'session_expired'          => 'Your session has expired. Please refresh the page.',
        'confirm_title'            => 'Are you sure?',
        'confirm_button'           => 'Confirm',
        'cancel_button'            => 'Cancel',
        'delete_item_title'        => 'Delete item?',
        'delete_item_text'         => 'This action cannot be undone.',
        'yes_delete'               => 'Yes, delete',
        'delete_selected_title'    => 'Delete selected items?',
        'confirm_bulk_action_title' => 'Confirm bulk action?',
        'bulk_action_impact'       => "{message}\n\n{count} item(s) will be affected.",
        'yes_continue'             => 'Yes, continue',
        'continue_action'          => 'Continue',
        'request_failed'           => 'Request failed.',
        'saving'                   => 'Saving…',
        'saved'                    => 'Saved.',
        'failed_generic'           => 'Failed.',
        'not_authorised'           => 'You are not authorised to do this.',

        'form' => [
            'not_authorised'        => 'You are not authorised to do this.',
        ],
        'layout' => [
            'could_not_switch_language' => 'Could not switch language.',
            'could_not_switch_country'  => 'Could not switch country.',
        ],
    ],

    // ─── Admin portal ───────────────────────────────────────────────────────
    'admin' => [

        'vendors' => [
            'approve_confirm'               => 'Approve this vendor account?',
            'reactivate_confirm'            => 'Reactivate this vendor?',
            'reactivate_team_member_confirm' => 'Reactivate this team member?',
            'verify_document_confirm'       => 'Verify this document?',
            'verify_bank_confirm'           => 'Verify this bank account?',
            'release_hold_confirm'         => 'Release payout hold?',
            'deactivate_team_notice'       => 'Deactivate {name}? They will lose portal access until reactivated.',
            'suspended_success'            => '{name} suspended.',
            'approved_success'             => '{name} approved.',
            'rejected_success'             => '{name} rejected.',
            'reactivated_success'         => '{name} reactivated.',
            'suspension_failed'           => 'Suspension failed.',
            'approval_failed'             => 'Approval failed.',
            'rejection_failed'            => 'Rejection failed.',
            'reactivation_failed'        => 'Reactivation failed.',
            'bulk_suspension_failed'     => 'Bulk suspension failed.',
            'bulk_reactivation_failed'   => 'Bulk reactivation failed.',
            'bulk_action_failed'         => 'Bulk action failed.',
            'document_verified'           => 'Document verified.',
            'verification_failed'         => 'Verification failed.',
            'document_rejected'          => 'Document rejected.',
            'select_one_document_type'    => 'Please select at least one document type.',
            'document_request_sent'      => 'Document request sent.',
            'failed_to_send_request'     => 'Failed to send request.',
            'failed_to_send'             => 'Failed to send.',
            'failed_to_release_hold'      => 'Failed to release hold.',
            'profile_updated'            => 'Profile updated.',
            'update_failed'              => 'Update failed.',
            'auto_suspended_warning'      => 'Vendor auto-suspended: 3 active strikes or critical strike issued.',
            'strike_failed'               => 'Strike failed.',
            'critical_strike_warning'      => 'Critical strike will auto-suspend the vendor immediately.',
            'view_label'                  => 'View',
            'more_actions_label'          => 'More actions',
        ],

        'banners' => [
            'deleting'              => 'Deleting…',
            'deleted'               => 'Banner deleted.',
            'delete_failed'         => 'Failed to delete banner.',
            'delete_label'          => 'Delete',
            'duplicating'           => 'Duplicating…',
            'duplicated'            => 'Banner duplicated.',
            'duplicate_failed'      => 'Failed to duplicate banner.',
            'duplicate_label'       => 'Duplicate',
            'remove_image_confirm'  => 'Remove this image?',
            'image_removed'         => 'Image removed.',
            'image_remove_failed'   => 'Failed to remove image.',
            'end_after_start'       => 'End must be after start',
            'duration_label'        => 'Duration: {days} day(s)',
            'fix_date_errors'       => 'Please fix date errors before saving.',
        ],

        'vendor_applications' => [
            'status_updated'              => 'Status updated.',
            'assigned'                    => 'Assigned.',
            'could_not_verify_document'   => 'Could not verify document.',
            'could_not_reject_document'   => 'Could not reject document.',
            'approving'                   => 'Approving…',
            'vendor_approved'             => 'Vendor approved.',
            'approval_failed'             => 'Approval failed.',
            'confirm_approval_label'      => 'Confirm Approval',
            'rejecting'                   => 'Rejecting…',
            'application_rejected'        => 'Application rejected.',
            'rejection_failed'            => 'Rejection failed.',
            'reject_application_label'    => 'Reject Application',
            'sending'                     => 'Sending…',
            'request_sent'                => 'Request sent.',
            'send_request_label'          => 'Send Request',
            'under_review_label'          => 'Under Review',
            'business_license'            => 'Business License',
            'tax_certificate'              => 'Tax Certificate',
            'owner_id'                    => 'Owner ID',
        ],

        'app_contexts' => [
            'failed_to_save'                    => 'Failed to save.',
            'failed_to_save_country_assignment' => 'Failed to save country assignment.',
            'navigation_item_saved'              => 'Navigation item saved.',
            'failed_to_save_navigation_item'     => 'Failed to save navigation item.',
        ],

        'shipping_subsidies' => [
            'status_active'          => 'Active',
            'status_inactive'        => 'Inactive',
            'edit_label'              => 'Edit',
            'deactivate_label'        => 'Deactivate',
            'cap_hint_with_currency'  => 'Max amount the platform covers per delivery, in {symbol} (plain integer, no decimals).',
            'cap_hint_generic'        => 'Max amount the platform covers per delivery, in the smallest currency unit.',
            'deactivate_confirm'      => 'Deactivate this subsidy? It will stop applying to new orders.',
            'deactivated_success'     => 'Subsidy deactivated.',
            'deactivate_failed'       => 'Failed to deactivate.',
            'updated_success'         => 'Subsidy updated.',
            'created_success'         => 'Subsidy created.',
            'save_failed'             => 'Save failed.',
            'split_vendor_label'      => 'vendor',
            'split_admin_label'       => 'admin',
        ],

        'page_builder' => [
            'loading'              => 'Loading…',
            'no_results'           => 'No results.',
            'could_not_add_item'   => 'Could not add item.',
            'could_not_remove_item' => 'Could not remove item.',
            'saved_revision'       => 'Saved (rev #{revision})',
            'no_slides_yet'        => 'No slides yet. Click "Add slide" above.',
            'edit_label'           => 'Edit',
            'delete_label'         => 'Delete',
            'slider_updated'       => 'Slider — updated',
        ],

        'warehouse_detail' => [
            'adjustment_failed'          => 'Adjustment failed.',
            'inventory_adjusted'         => 'Inventory adjusted.',
            'network_error'              => 'Network error.',
            'save_failed'                => 'Save failed.',
            'vendor_limit_saved'         => 'Vendor limit saved.',
            'remove_vendor_limit_confirm' => 'Remove this vendor storage limit?',
            'vendor_limit_removed'       => 'Vendor limit removed.',
        ],
    ],

    // ─── Partner portal ─────────────────────────────────────────────────────
    'partner' => [
        'warehouse' => [
            'registering'            => 'Registering…',
            'registered_success'     => 'Warehouse registered successfully.',
            'register_failed'        => 'Failed to register warehouse.',
            'register_label'         => 'Register warehouse',
            'already_in_list'        => 'This product is already in the list.',
            'add_item_first'         => 'Please add at least one item.',
            'creating'               => 'Creating…',
            'transfer_created'       => 'Transfer created successfully.',
            'transfer_create_failed' => 'Failed to create transfer.',
            'create_transfer_label'  => 'Create transfer',
            'failed_to_load_inventory' => 'Failed to load inventory.',
            'out_of_stock'           => 'Out of stock',
            'low_stock'              => 'Low stock',
            'in_stock_ok'            => 'OK',
            'no_results'             => 'No results',
            'showing_range'          => 'Showing {from}–{to} of {total}',
            'prev'                   => '← Prev',
            'next'                   => 'Next →',
            'provide_reason'         => 'Please provide a reason.',
            'stock_adjusted'         => 'Stock adjusted successfully.',
            'adjust_failed'          => 'Failed to adjust stock.',
            'warehouse_updated'      => 'Warehouse updated.',
            'update_failed'          => 'Failed to update warehouse.',
            'status_updated'         => 'Warehouse status updated.',
            'operation_failed'       => 'Operation failed.',
        ],

        'warehouses' => [
            'no_transfers_found'          => 'No transfers found.',
            'showing_range_transfers'     => 'Showing _START_–_END_ of _TOTAL_ transfers',
            'no_transfers'                => 'No transfers',
            'no_transfers_match_search'   => 'No transfers match your search.',
            'transfer_shipped'            => 'Transfer marked as shipped.',
            'failed_update_transfer'      => 'Failed to update transfer.',
            'cancel_transfer_confirm'     => 'Are you sure you want to cancel this transfer?',
            'transfer_cancelled'          => 'Transfer cancelled.',
            'failed_cancel_transfer'      => 'Failed to cancel transfer.',
        ],
    ],

    // ─── Marketer portal ────────────────────────────────────────────────────
    'marketer' => [
        'qr_generator' => [
            'generating'          => 'Generating…',
            'could_not_generate'  => 'Could not generate QR',
            'network_error'       => 'Network error — please try again',
            'generate_label'      => 'Generate',
            'share_default_text'  => 'Check this out!',
            'copied'              => '✓ Copied!',
        ],
        'dashboard' => [
            'clicks_label'   => 'Clicks',
            'earnings_label' => 'Earnings (SAR)',
            'copied'         => '✓ Copied!',
        ],
    ],

    // ─── Travel Agency portal ───────────────────────────────────────────────
    'travel_agency' => [
        'reports' => [
            'bookings_label' => 'Bookings',
        ],

        'bank_accounts' => [
            'submitted'          => 'Submitted.',
            'generic_form_error' => 'Something went wrong. Please check the form.',
            'delete_confirm'     => 'Delete this bank account?',
            'cannot_delete'      => 'This account cannot be deleted.',
        ],
    ],

];

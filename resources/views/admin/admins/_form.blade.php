{{--
    Admin form partial.
    Include with: @include('admin.admins._form', ['mode' => 'create'])
                  @include('admin.admins._form', ['mode' => 'edit'])

    Expected variables: $allRoles (Role collection with permissions loaded),
                        $countries (Country collection), $model (Admin|null)
--}}
@php
    $model  = $model ?? null;
    $isEdit = $mode === 'edit' && $model !== null;

    $val = fn($field, $default = '') => old($field, $isEdit ? ($model->{$field} ?? $default) : $default);

    // Build role→permissions map for Alpine.js
    $rolePermissions = [];
    foreach ($allRoles as $role) {
        $rolePermissions[$role->name] = $role->permissions->pluck('name')->sort()->values()->toArray();
    }

    $currentRoles = old('roles', $isEdit ? $model->getRoleNames()->toArray() : []);

    $statusOptions = ['active' => 'Active', 'inactive' => 'Inactive'];
    $countryOptions = ['' => '— No Country —'] + $countries->pluck('name_en', 'id')->toArray();
@endphp

<div class="space-y-6"
     x-data="{
        selectedRoles: {{ json_encode($currentRoles) }},
        rolePermissions: {{ json_encode($rolePermissions) }},
        get effectivePermissions() {
            if (!this.selectedRoles.length) return [];
            const all = new Set();
            this.selectedRoles.forEach(r => {
                (this.rolePermissions[r] || []).forEach(p => all.add(p));
            });
            return [...all].sort();
        },
        showGeneratedPassword: false,
        generatedPassword: ''
     }">

    {{-- ── Page Header ─────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">
                {{ $isEdit ? 'Edit Administrator: ' . e($model->name) : 'New Administrator' }}
            </h1>
            @if($isEdit)
                <p class="text-sm text-gray-500 mt-1">
                    Member since {{ $model->created_at->format('M d, Y') }}
                    @if($model->last_login_at)
                        · Last login {{ $model->last_login_at->diffForHumans() }}
                    @endif
                </p>
            @endif
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.admins.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary" id="save-btn">
                {{ $isEdit ? 'Save Changes' : 'Create Administrator' }}
            </button>
        </div>
    </div>

    {{-- ── Generated Password Alert (create mode only) ─────────────────── --}}
    @if(!$isEdit)
        <div x-show="showGeneratedPassword" x-cloak
             class="border border-green-300 bg-green-50 rounded-lg px-4 py-3">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-green-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                <div>
                    <p class="text-sm font-medium text-green-800">Administrator created. Copy the generated password now — it will not be shown again.</p>
                    <div class="mt-2 flex items-center gap-2">
                        <code class="text-sm font-mono bg-white border border-green-200 rounded px-2 py-1 text-green-900 select-all"
                              x-text="generatedPassword"></code>
                        <button type="button"
                                @click="navigator.clipboard.writeText(generatedPassword); window.Toast?.success('Copied!')"
                                class="btn btn-xs btn-secondary">Copy</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Two-column layout ───────────────────────────────────────────── --}}
    <div class="flex gap-6 items-start">

        {{-- ═══════ LEFT: Admin Details ═══════ --}}
        <div class="flex-1 min-w-0 space-y-4">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Administrator Details</h2>
                </div>
                <div class="px-5 py-5 space-y-4">

                    <x-form-input
                        name="name"
                        label="Full Name"
                        type="text"
                        :value="$val('name')"
                        placeholder="e.g. Jane Smith"
                        required
                    />

                    <x-form-input
                        name="email"
                        label="Email Address"
                        type="email"
                        :value="$val('email')"
                        placeholder="admin@example.com"
                        required
                    />

                    <x-form-input
                        name="phone"
                        label="Phone"
                        type="text"
                        :value="$val('phone')"
                        placeholder="+20 10 0000 0000"
                    />

                    <x-form-select
                        name="country_id"
                        label="Country"
                        :options="$countryOptions"
                        :value="$val('country_id')"
                        placeholder="— No Country —"
                        select2
                    />

                    <x-form-select
                        name="status"
                        label="Status"
                        :options="$statusOptions"
                        :value="$val('status', 'active')"
                        required
                    />

                </div>
            </div>

            {{-- ── Edit mode: Reset Password ──────────────────────────── --}}
            @if($isEdit)
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h2 class="text-sm font-semibold text-gray-900">Password</h2>
                    </div>
                    <div class="px-5 py-5 flex items-center justify-between">
                        <p class="text-sm text-gray-500">
                            Reset this administrator's password. They will receive an email with their new credentials.
                        </p>
                        <button type="button" id="reset-password-btn"
                                data-url="{{ route('admin.admins.reset-password', $model->id) }}"
                                class="btn btn-secondary btn-sm shrink-0 ml-4">
                            Reset Password
                        </button>
                    </div>
                </div>
            @endif
        </div>

        {{-- ═══════ RIGHT: Roles & Permissions ═══════ --}}
        <div class="w-80 shrink-0 space-y-4">

            {{-- Roles card --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Roles</h2>
                    <p class="text-xs text-gray-500 mt-0.5">At least one role is required.</p>
                </div>
                <div class="px-5 py-4 space-y-2">
                    @error('roles')
                        <p class="text-xs text-red-600 mb-2">{{ $message }}</p>
                    @enderror

                    @foreach($allRoles as $role)
                        <label class="flex items-center gap-2.5 cursor-pointer group">
                            <input
                                type="checkbox"
                                name="roles[]"
                                value="{{ $role->name }}"
                                x-model="selectedRoles"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            >
                            <span class="text-sm text-gray-700 group-hover:text-gray-900">{{ $role->name }}</span>
                            <span class="text-xs text-gray-400 ml-auto">{{ $role->permissions->count() }} perms</span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Effective permissions card --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900">Effective Permissions</h2>
                    <span class="text-xs text-gray-500 bg-gray-100 rounded-full px-2 py-0.5" x-text="effectivePermissions.length"></span>
                </div>
                <div class="px-5 py-4 max-h-72 overflow-y-auto">
                    <template x-if="!effectivePermissions.length">
                        <p class="text-xs text-gray-400 italic">Select a role to see its permissions.</p>
                    </template>
                    <ul class="space-y-0.5">
                        <template x-for="perm in effectivePermissions" :key="perm">
                            <li class="text-xs text-gray-600 font-mono py-0.5 border-b border-gray-50 last:border-0" x-text="perm"></li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

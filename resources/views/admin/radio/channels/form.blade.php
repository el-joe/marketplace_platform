@extends('layouts.admin')

@section('title', isset($channel) ? 'Edit Radio Channel' : 'New Radio Channel')

@section('content')
<div class="p-6 max-w-2xl">

    <div class="mb-6">
        <a href="{{ route('admin.radio.channels.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Channels</a>
        <h1 class="text-xl font-bold text-gray-900 mt-1">
            {{ isset($channel) ? 'Edit Channel' : 'New Radio Channel' }}
        </h1>
    </div>

    <form method="POST"
          action="{{ isset($channel) ? route('admin.radio.channels.update', $channel) : route('admin.radio.channels.store') }}"
          class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
        @csrf
        @if(isset($channel)) @method('PUT') @endif

        @if($errors->any())
        <div class="p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
        @endif

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name (English) <span class="text-red-500">*</span></label>
                <input name="name_en" value="{{ old('name_en', $channel->name_en ?? '') }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name (Arabic) <span class="text-red-500">*</span></label>
                <input name="name_ar" value="{{ old('name_ar', $channel->name_ar ?? '') }}" required dir="rtl"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type <span class="text-red-500">*</span></label>
                <select name="type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    @foreach(['audio','video'] as $t)
                    <option value="{{ $t }}" {{ old('type', $channel->type ?? '') === $t ? 'selected' : '' }}>
                        {{ ucfirst($t) }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                <select name="country_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">All Countries</option>
                    @foreach($countries as $c)
                    <option value="{{ $c->id }}" {{ old('country_id', $channel->country_id ?? '') == $c->id ? 'selected' : '' }}>
                        {{ $c->name_en }}
                    </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Live Stream URL</label>
            <input name="stream_url" type="url" value="{{ old('stream_url', $channel->stream_url ?? '') }}"
                   placeholder="https://stream.example.com/live/channel.m3u8"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-primary-500">
            <p class="text-xs text-gray-400 mt-1">HLS (.m3u8) or RTMP URL — used when a schedule slot is active</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Fallback Media Path</label>
            <input name="fallback_media_path" value="{{ old('fallback_media_path', $channel->fallback_media_path ?? '') }}"
                   placeholder="radio/fallback/channel-loop.mp3"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-primary-500">
            <p class="text-xs text-gray-400 mt-1">Played when no live slot is active (storage path or URL)</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Thumbnail Path</label>
            <input name="thumbnail_path" value="{{ old('thumbnail_path', $channel->thumbnail_path ?? '') }}"
                   placeholder="radio/thumbnails/channel.jpg"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-primary-500">
        </div>

        <div class="flex items-center gap-3">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" id="is_active"
                   {{ old('is_active', $channel->is_active ?? true) ? 'checked' : '' }}
                   class="rounded border-gray-300 text-primary-600">
            <label for="is_active" class="text-sm text-gray-700">Channel is active (visible to customers)</label>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-5 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700">
                {{ isset($channel) ? 'Update Channel' : 'Create Channel' }}
            </button>
            <a href="{{ route('admin.radio.channels.index') }}"
               class="px-5 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection

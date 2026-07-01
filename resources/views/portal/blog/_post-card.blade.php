<article class="bg-white rounded-2xl border border-gray-200 overflow-hidden hover:shadow-md transition-shadow group flex flex-col">
    @if($post->featured_image_path)
        <a href="{{ route('portal.blog.show', $post->slug) }}" class="block aspect-video overflow-hidden">
            <img src="{{ Storage::url($post->featured_image_path) }}"
                 alt="{{ $post->{'featured_image_alt_' . $locale} }}"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                 loading="lazy">
        </a>
    @else
        <div class="aspect-video bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center text-gray-300 text-4xl">📝</div>
    @endif

    <div class="p-5 flex flex-col flex-1">
        <div class="flex items-center gap-2 mb-3">
            <span class="text-xs font-semibold rounded-full px-2.5 py-0.5"
                  style="background-color: {{ $post->category->color_hex ?? '#EFF6FF' }}20; color: {{ $post->category->color_hex ?? '#3B82F6' }}">
                {{ $post->category->{'name_' . $locale} }}
            </span>
            <span class="text-xs text-gray-400">· {{ $post->reading_time_minutes }} {{ $locale === 'ar' ? 'دقائق' : 'min' }}</span>
        </div>

        <h3 class="font-bold text-gray-900 leading-snug line-clamp-2 flex-1 group-hover:text-primary-600 transition-colors">
            <a href="{{ route('portal.blog.show', $post->slug) }}">
                {{ $post->{'title_' . $locale} }}
            </a>
        </h3>

        <p class="text-sm text-gray-500 mt-2 line-clamp-2 leading-relaxed">
            {{ $post->{'excerpt_' . $locale} }}
        </p>

        <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100">
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center text-primary-600 text-xs font-bold">
                    {{ mb_substr($post->author->name, 0, 1) }}
                </div>
                <span class="text-xs text-gray-500">{{ $post->author->name }}</span>
            </div>
            <span class="text-xs text-gray-400">
                {{ $post->published_at->format('d M Y') }}
            </span>
        </div>
    </div>
</article>

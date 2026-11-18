@props(['post'])

@if($post->image_url)
    <div class="mb-3 overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
        <img src="{{ $post->image_url }}" alt="Gambar postingan: {{ $post->title }}"
             loading="lazy" class="w-full max-h-96 object-cover">
    </div>
@endif
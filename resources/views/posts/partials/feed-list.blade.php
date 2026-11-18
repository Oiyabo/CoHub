@forelse ($posts as $post)
    <x-post-card :post="$post" />
@empty
    <div class="bg-white rounded-2xl p-8 text-center text-slate-400 text-sm">
        Belum ada postingan dalam komunitas.
    </div>
@endforelse

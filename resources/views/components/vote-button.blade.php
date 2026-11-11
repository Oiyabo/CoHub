@props([
    'postId'    => null,
    'commentId' => null,
    'score'     => 0,
    'userVote'  => null,   // 'upvote' | 'downvote' | null
])

@php
    $isPost    = !is_null($postId);
    $targetId  = $isPost ? $postId : $commentId;
    $voteUrl   = $isPost
        ? route('api.posts.vote', $targetId)
        : route('api.comments.vote', $targetId);
    $ownerId   = $isPost
        ? (isset($post) ? $post->user_id : null)
        : (isset($comment) ? $comment->user_id : null);
@endphp

<div x-data="voteWidget({
        url: '{{ $voteUrl }}',
        initialScore: {{ $score }},
        initialVote: {{ $userVote ? '"' . $userVote . '"' : 'null' }},
     })"
     class="flex items-center bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl px-2.5 py-1.5 gap-1 transition-colors select-none">

    {{-- Upvote --}}
    @auth
        <button type="button"
                @click="vote('upvote')"
                :class="currentVote === 'upvote'
                    ? 'text-orange-500 scale-110'
                    : 'text-slate-400 hover:text-orange-400'"
                class="transition-all font-black leading-none text-sm"
                title="Upvote">▲</button>
    @else
        <a href="{{ route('login') }}" class="text-slate-400 hover:text-orange-400 transition-colors font-black leading-none text-sm" title="Masuk untuk vote">▲</a>
    @endauth

    {{-- Skor --}}
    <span class="font-mono font-bold text-xs min-w-[1.75rem] text-center leading-none"
          :class="currentVote === 'upvote' ? 'text-orange-500' : currentVote === 'downvote' ? 'text-blue-500' : 'text-slate-700'"
          x-text="displayScore"></span>

    {{-- Downvote --}}
    @auth
        <button type="button"
                @click="vote('downvote')"
                :class="currentVote === 'downvote'
                    ? 'text-blue-500 scale-110'
                    : 'text-slate-400 hover:text-blue-400'"
                class="transition-all font-black leading-none text-sm"
                title="Downvote">▼</button>
    @else
        <a href="{{ route('login') }}" class="text-slate-400 hover:text-blue-400 transition-colors font-black leading-none text-sm" title="Masuk untuk vote">▼</a>
    @endauth

</div>

@once
<script>
    function voteWidget({ url, initialScore, initialVote }) {
        return {
            currentVote: initialVote,
            displayScore: initialScore,
            isLoading: false,

            async vote(type) {
                if (this.isLoading) return;
                this.isLoading = true;

                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ vote_type: type }),
                    });

                    const data = await res.json();

                    if (data.success) {
                        this.displayScore = data.score;
                        this.currentVote  = data.user_vote;
                    } else if (res.status === 403) {
                        // Self-vote atau akses ditolak — beri notifikasi halus
                        const el = this.$el;
                        el.style.transition = 'transform 0.1s';
                        el.style.transform = 'translateX(4px)';
                        setTimeout(() => { el.style.transform = 'translateX(-4px)'; }, 100);
                        setTimeout(() => { el.style.transform = ''; }, 200);
                    }
                } catch (err) {
                    console.error('Vote error:', err);
                } finally {
                    this.isLoading = false;
                }
            }
        };
    }
</script>
@endonce

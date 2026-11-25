@props([
    'postId'    => null,
    'commentId' => null,
    'score'     => 0,
    'userVote'  => null,
    'size'      => 'md',
])

@php
    $isPost   = !is_null($postId);
    $targetId = $isPost ? $postId : $commentId;
    $voteUrl  = $isPost
        ? route('api.posts.vote', $targetId)
        : route('api.comments.vote', $targetId);
    $isSmall  = $size === 'sm';
@endphp

<div x-data="voteWidget({
        url: '{{ $voteUrl }}',
        initialScore: {{ $score }},
        initialVote: {{ $userVote ? '"' . $userVote . '"' : 'null' }},
     })"
     data-no-nav
     class="relative inline-flex items-center bg-slate-100 hover:bg-slate-200 text-slate-700 {{ $isSmall ? 'rounded-lg px-2 py-1 gap-1' : 'rounded-xl px-2.5 py-1.5 gap-1' }} transition-colors select-none">

    @auth
        <button type="button"
                @click="vote('upvote')"
                :class="currentVote === 'upvote'
                    ? 'text-orange-500 scale-110'
                    : 'text-slate-400 hover:text-orange-400'"
                class="transition-all font-black leading-none {{ $isSmall ? 'text-xs' : 'text-sm' }} cursor-pointer"
                title="Upvote">▲</button>
    @else
        <button type="button"
                @click="$dispatch('open-login')"
                class="text-slate-400 hover:text-orange-400 transition-colors font-black leading-none {{ $isSmall ? 'text-xs' : 'text-sm' }} cursor-pointer"
                title="Masuk untuk vote">▲</button>
    @endauth

    <span class="font-mono font-bold {{ $isSmall ? 'text-[11px] min-w-[1.25rem]' : 'text-xs min-w-[1.75rem]' }} text-center leading-none"
          :class="currentVote === 'upvote' ? 'text-orange-500' : currentVote === 'downvote' ? 'text-blue-500' : 'text-slate-700'"
          x-text="displayScore"></span>

    @auth
        <button type="button"
                @click="vote('downvote')"
                :class="currentVote === 'downvote'
                    ? 'text-blue-500 scale-110'
                    : 'text-slate-400 hover:text-blue-400'"
                class="transition-all font-black leading-none {{ $isSmall ? 'text-xs' : 'text-sm' }} cursor-pointer"
                title="Downvote">▼</button>
    @else
        <button type="button"
                @click="$dispatch('open-login')"
                class="text-slate-400 hover:text-blue-400 transition-colors font-black leading-none {{ $isSmall ? 'text-xs' : 'text-sm' }} cursor-pointer"
                title="Masuk untuk vote">▼</button>
    @endauth

    <div x-show="errorMsg"
         x-transition.opacity
         style="display: none;"
         class="absolute left-0 -top-8 z-30 whitespace-nowrap bg-rose-600 text-white text-[10px] font-semibold px-2.5 py-1 rounded-lg shadow-md"
         x-text="errorMsg"></div>

</div>

@once
<script>
    window.voteWidget = function ({ url, initialScore, initialVote }) {
        return {
            currentVote: initialVote,
            displayScore: initialScore,
            isLoading: false,
            errorMsg: '',
            errorTimer: null,

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
                        this.errorMsg     = '';
                    } else if (res.status === 403) {
                        const el = this.$el;
                        el.style.transition = 'transform 0.1s';
                        el.style.transform = 'translateX(4px)';
                        setTimeout(() => { el.style.transform = 'translateX(-4px)'; }, 100);
                        setTimeout(() => { el.style.transform = ''; }, 200);
                        this.errorMsg = data.message || 'Tidak dapat memvote konten sendiri.';
                        if (this.errorTimer) clearTimeout(this.errorTimer);
                        this.errorTimer = setTimeout(() => { this.errorMsg = ''; }, 2200);
                    }
                } catch (err) {
                    console.error('Vote error:', err);
                } finally {
                    this.isLoading = false;
                }
            }
        };
    };
</script>
@endonce

@php
    $depth = $depth ?? 0;
@endphp

@foreach($comments as $comment)
    @include('components.comment-item', [
        'comment' => $comment,
        'depth' => $depth
    ])
@endforeach
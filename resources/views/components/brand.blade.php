<span {{ $attributes->class(['app-brand']) }}>
    <img class="app-brand-mark" src="{{ asset('images/brand-mark.svg') }}" width="44" height="44" alt="">
    <span class="app-brand-wordmark">{{ config('app.name', 'My App') }}</span>
</span>

@php
    $mailLogoUrl = \App\Support\MailBrand::logoUrl();
    $mailSlogan = \App\Support\MailBrand::SLOGAN;
    $brandSubtitle = $brandSubtitle ?? null;
@endphp
<div class="brand-header">
    <img src="{{ $mailLogoUrl }}" alt="Troosolar" class="brand-logo" width="280" style="max-width:280px;width:100%;height:auto;display:block;margin:0 auto 8px;" />
    <p class="brand-slogan">{{ $mailSlogan }}</p>
    @if(!empty($brandSubtitle))
        <p class="brand-subtitle">{{ $brandSubtitle }}</p>
    @endif
</div>

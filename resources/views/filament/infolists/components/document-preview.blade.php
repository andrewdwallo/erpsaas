@php
    $document = \App\DTO\DocumentDTO::fromModel($getRecord());
    $template = $getTemplate();
@endphp

{!! $document->getFontHtml() !!}

<style>
    .inv-paper {
        font-family: '{{ $document->font->getLabel() }}', sans-serif;
    }
</style>

<div {{ $attributes }}>
    @include("filament.infolists.components.document-templates.{$template->value}", [
        'document' => $document,
        'preview' => false,
    ])
</div>

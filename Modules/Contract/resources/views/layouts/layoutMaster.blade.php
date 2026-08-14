@isset($pageConfigs)
{!! Helper::updatePageConfig($pageConfigs) !!}
@endisset
@php
$configData = Helper::appClasses();
@endphp

@isset($configData["layout"])
@include((( $configData["layout"] === 'horizontal') ?
 'contract::layouts.layoutFront' : 'contract::layouts.contentNavbarLayout') 
 )))
@endisset


@yield('script')
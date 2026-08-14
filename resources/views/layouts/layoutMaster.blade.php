@isset($pageConfigs)
{!! Helper::updatePageConfig($pageConfigs) !!}
@endisset
@php
$configData = Helper::appClasses();
@endphp

@isset($configData["layout"])
@include((( $configData["layout"] === 'horizontal') ? 'layouts.horizontalLayout' :
(( $configData["layout"] === 'blank') ? 'layouts.blankLayout' :
(($configData["layout"] === 'front') ? 'layouts.layoutFront' : 'layouts.contentNavbarLayout') )))
@endisset

<script>
    var APP_URL = "{{ url('/') }}";

    if( "{{ session()->get('contractSessionUserAccessType') }}" == 'View-Only'){ 
    const buttons = document.querySelectorAll('button');
    buttons.forEach(button => { button.disabled = true; }); 
    const buttonsa = document.querySelectorAll('a.btn'); 
    buttonsa.forEach(button => { button.style.pointerEvents = 'none'; }); 
    } 

    console.log("{{ 'access type'.session()->get('contractSessionUserAccessType') }}");     
</script>


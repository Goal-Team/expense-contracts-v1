<!DOCTYPE html>
@php
$menuFixed = ($configData['layout'] === 'vertical') ? ($menuFixed ?? '') : (($configData['layout'] === 'front') ? '' : $configData['headerType']);
$navbarType = ($configData['layout'] === 'vertical') ? ($configData['navbarType'] ?? '') : (($configData['layout'] === 'front') ? 'layout-navbar-fixed': '');
$isFront = ($isFront ?? '') == true ? 'Front' : '';
$contentLayout = (isset($container) ? (($container === 'container-xxl') ? "layout-compact" : "layout-wide") : "");
@endphp

<html lang="{{ session()->get('locale') ?? app()->getLocale() }}" class="{{ $configData['style'] }}-style {{($contentLayout ?? '')}} {{ ($navbarType ?? '') }} {{ ($menuFixed ?? '') }} {{ $menuCollapsed ?? '' }} {{ $menuFlipped ?? '' }} {{ $menuOffcanvas ?? '' }} {{ $footerFixed ?? '' }} {{ $customizerHidden ?? '' }}" dir="{{ $configData['textDirection'] }}" data-theme="{{ $configData['theme'] }}" data-assets-path="{{ asset('/assets') . '/' }}" data-base-url="{{url('/')}}" data-framework="laravel" data-template="{{ $configData['layout'] . '-menu-' . $configData['themeOpt'] . '-' . $configData['styleOpt'] }}">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" 
      content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0"/>

  <title>@yield('title')</title>
  
  <!-- laravel CRUD token -->
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" />

  

  <!-- Include Styles -->
  <!-- $isFront is used to append the front layout styles only on the front layout otherwise the variable will be blank -->
  @include('layouts/sections/styles' . $isFront)

  <!-- Include Scripts for customizer, helper, analytics, config -->
  <!-- $isFront is used to append the front layout scriptsIncludes only on the front layout otherwise the variable will be blank -->
  @include('layouts/sections/scriptsIncludes' . $isFront)

  <!-- Per-page scripts that must start before the body renders - today only the
       dashboard's option-list prefetch. Empty on every other page. -->
  @yield('head-prefetch')
</head>
<style>
#load{
    /*width:100%;*/
    /*height:100%;*/
    /*position:fixed;*/
    /*z-index:9999;*/
    /*background:url("{{url('assets/logo/OnTrackLogo.png')}}") no-repeat center center rgb(255 255 255 / 80%);*/
    /*background-size: 150px auto;*/
}   
.layout-navbar-fixed .layout-wrapper:not(.layout-horizontal) .layout-page:before{
    height: 2.875rem !important;
}
.highlightQuillCustomVar{
    background-color: #604a9e;
    color: #fff;
}
.loader-wrapper {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgb(255 255 255 / 80%);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    flex-direction: column;
}

.loader-content {
    text-align: center;
}

.loader-logo {
    width: 100px;
    height: auto;
    margin-bottom: 20px;
    animation: pulse 1.5s infinite ease-in-out;
}

.loader-text {
    font-size: 16px;
    color: #333;
    margin-bottom: 20px;
    animation: fadeInUp 1.5s ease-in-out infinite alternate;
}

/* Animations */
@keyframes fadeInUp {
    0% {
        opacity: 0.3;
        transform: translateY(5px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.05);
        opacity: 0.8;
    }
}
</style>
<body>
@if(env('enable_preloader'))
<div id="load">
    <div class="loader-wrapper">
        <div class="loader-content">
            <img src="{{url('assets/logo/OnTrackLogo.png')}}" alt="Logo" class="loader-logo">
            <div class="loader-text">Loading @yield('title') data for you, please wait…</div>
            <small class="position-relative bottom-0"><span role="button" class="btn btn-xs rounded-pill btn-icon btn-outline-warning fw-bold text-dark"><strong>AI</strong></span> Powered</small>
        </div>
    </div>
</div>  
@endif
  <!-- Layout Content -->
  @yield('layoutContent')
  <!--<div class="loader-overlay">-->
  <!--  <span class="loader"></span>-->
  <!--</div>-->
  <!--/ Layout Content -->

  

  <!-- Include Scripts -->
  <!-- $isFront is used to append the front layout scripts only on the front layout otherwise the variable will be blank -->
  @include('layouts/sections/scripts' . $isFront)

</body>
<script>
    document.onreadystatechange = function () {
    if(document.getElementById('load')){
      var state = document.readyState;
          document.getElementById('load').style.visibility="visible";
          console.log(document.readyState);
      if (state == 'complete') {
          setTimeout(function(){
             document.getElementById('load').style.visibility="hidden";
          },1000);
      }
    }
}

addEventListener("submit", (event) => {
    setLoaderCookie('preload', true, 1);
    //console.log('loader Started');
    if(document.getElementById('load')){
        document.getElementById('load').style.visibility="visible";
    }
    
    setInterval(function(){
        let processDone = getLoaderCookie('preload');
        //console.log(processDone);
        if(!processDone){
            document.getElementById('load').style.visibility="hidden";
        }
    }, 3000)
    
});

function setLoaderCookie(cname, cvalue, exdays) {
  const d = new Date();
  d.setTime(d.getTime() + (exdays*24*60*60*1000));
  let expires = "expires="+ d.toUTCString();
  document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}
function setLoaderCookie(cname, cvalue, exdays) {
  const d = new Date();
  d.setTime(d.getTime() + (exdays*24*60*60*1000));
  let expires = "expires="+ d.toUTCString();
  document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function getLoaderCookie(cname) {
  let name = cname + "=";
  let decodedCookie = decodeURIComponent(document.cookie);
  let ca = decodedCookie.split(';');
  for(let i = 0; i <ca.length; i++) {
    let c = ca[i];
    while (c.charAt(0) == ' ') {
      c = c.substring(1);
    }
    if (c.indexOf(name) == 0) {
      return c.substring(name.length, c.length);
    }
  }
  return "";
}

</script>
</html>

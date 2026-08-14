@php
$configData = Helper::appClasses();
@endphp
<style>
    .ms-storage{
        background-image: linear-gradient(#4facfe, #00f2fe);
        color: transparent;
        background-clip: text;
    }
    .gl-storage{
        background-image: linear-gradient(to right, #4285f4 25%, #34a853 25%, #34a853 50%, #fbbc05 50%, #fbbc05 75%, #ea4335 75%);
        color: transparent;
        background-clip: text;
    }
    
    .blink {
      animation: blink-animation 1s steps(5, start) infinite;
      -webkit-animation: blink-animation 1s steps(5, start) infinite;
    }
    @keyframes blink-animation {
      to {
        visibility: hidden;
      }
    }
    @-webkit-keyframes blink-animation {
      to {
        visibility: hidden;
      }
    }    
</style>
<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">

  <!-- ! Hide app brand if navbar-full -->
  @if(!isset($navbarFull))
  <div class="app-brand demo">
    <a href="{{url('/')}}" class="app-brand-link">
      <!--<span class="app-brand-logo demo">-->
      <!--  @include('_partials.macros',["height"=>20])-->
      <!--</span>-->
      <!--<span class="app-brand-text demo menu-text fw-bold">Contracts</span>-->
      <img src="{{url('assets/logo/OnTrackLogo.png')}}" alt="ONTRACK" width="180">
    </a>

    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
      <i class="ti menu-toggle-icon d-none d-xl-block ti-sm align-middle"></i>
      <i class="ti ti-x d-block d-xl-none ti-sm align-middle"></i>
    </a>
  </div>
    <span class="badge badge-outline-warning blink bg-{{ env('test_environment') ? 'warning' : 'success' }}">
      {{ env('test_environment') ? 'Test Environment' : 'Live' }}
    </span>  
  @endif


  <div class="menu-inner-shadow"></div>

  <ul class="menu-inner py-1">

    @if($menuData[0]->menu ?? false)
    @foreach ($menuData[0]->menu as $menu)

    {{-- adding active and open class if child is active --}}

    {{-- menu headers --}}
    @if (isset($menu->menuHeader))
    <li class="menu-header small text-uppercase">
      <span class="menu-header-text">{{ __($menu->menuHeader) }}</span>
    </li>

    @else

    {{-- active menu method --}}
    @php
    $activeClass = null;
    $currentRouteName = Route::currentRouteName();
    $currentRoleLogged = session()->get('contractSessionUserRole');
    $allowedAdminRoles = ['Admin', 'Branch Head'];
    $allowedSAdminRoles = ['Super Admin'];
    
    $slugsArr = explode(",", $menu->slug);
    

    if(isset($menu->onlyAdmin) && $menu->onlyAdmin){
        if((!in_array($currentRoleLogged, $allowedAdminRoles))){
            $menu = [];
            continue;
        }
    }  
    if(isset($menu->onlySAdmin) && $menu->onlySAdmin){
        if((!in_array($currentRoleLogged, $allowedSAdminRoles))){
            $menu = [];
            continue;
        }
    }     
    
    if(isset($menu->hideThis) && $menu->hideThis){
        $menu = [];
        continue;
    }    

    if (in_array($currentRouteName, $slugsArr)) {
    $activeClass = 'active';
    }
    
    if (isset($menu->submenu)) {
        if (in_array($currentRouteName, $slugsArr)) {
            $activeClass = 'active open';
        }
    }
    @endphp

    {{-- main menu --}}
    <li class="menu-item {{$activeClass.' '.$currentRoleLogged}} ">
      <a href="{{ isset($menu->url) ? url($menu->url) : 'javascript:void(0);' }}" class="{{ isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}" @if (isset($menu->target) and !empty($menu->target)) target="_blank" @endif>
        @isset($menu->icon)
        <i class="{{ $menu->icon }}"></i>
        @endisset
        <div>{{ isset($menu->name) ? __($menu->name) : '' }}</div>
        @isset($menu->badge)
        <div class="badge bg-{{ $menu->badge[0] }} rounded-pill ms-auto">{{ $menu->badge[1] }}</div>

        @endisset
      </a>

      {{-- submenu --}}
      @isset($menu->submenu)
      @include('layouts.sections.menu.submenu',['menu' => $menu->submenu])
      @endisset
    </li>
    @endif
    @endforeach
    @endif
  </ul>
 @if (session()->has('contractSessionUser'))
 @php
 $brandLogo = [
    'microsoft' => 'ti ti-cloud-filled',
    'google' => 'icon-base fab fa-google',
    'local' => 'ti ti-device-desktop'
 ];
 $brandLogoClass = [
    'microsoft' => 'ms-storage',
    'google' => 'gl-storage',
    'local' => 'loc-storage'
 ];
 @endphp
<div class="verticalMenuUserDiv">
    <div class="d-flex">
      <div class="flex-shrink-0 me-3">
        <div class="avatar avatar-online"> 
          <i class="ti ti-user-shield ms-2 bg-secondary rounded-circle p-2"></i>
        </div>
      </div>
      <div class="flex-grow-1">
        <span class="fw-medium d-block">
          {{ Helper::userInfo()->FirstName ?? '' }}
        </span>
        <small class="text-muted">
          {{ Helper::userInfo()->email ?? '' }}
        </small>
      </div>
    </div>
    <div class="d-grid px-2 pt-2 pb-1">
    <a class="btn btn-sm btn-danger d-flex waves-effect waves-light" href="{{url('/logout')}}">
      <small class="align-middle">Logout</small>
      <i class="ti ti-logout ms-2 ti-14px"></i>
    </a>
    </div>
    <div class="d-flex align-items-center bg-white py-2">
      <div class="flex-shrink-0 me-3">
        <div class=""> 
          <i class="{{ $brandLogo[strtolower(fileStorageType())] }} {{ $brandLogoClass[strtolower(fileStorageType())] }} ms-2 rounded-circle fs-3 p-2 border border-dark"></i>
        </div>
      </div>
      <div class="flex-grow-1">
        <span class="fw-bold d-block text-dark">
          {{ fileStorageType() }}
          
        </span>
        <small class="text-dark fw-medium">
          Storage
        </small>        
      </div>
    </div>
</div>
@endif
</aside>
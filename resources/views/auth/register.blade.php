@extends('layouts.auth')
@section('title', __('Sign up'))
@section('content')

@php
$check_users = \App\Models\User::count();
@endphp

<style type="text/css">
    .header-text {
        width: 440px;
        max-width: 100%;
        margin-left: auto;
        margin-right: auto;
        text-align: center;
        color: #FDBB1F;
        padding-bottom: 40px;
    }
    .header-text p {
        font-size: 18px;
        margin-bottom: 0;
    }
    .page-ath-alt .page-ath-header {
        padding-bottom: 0px;
    }
</style>
<div class="header-text">
    <p>Power the vision, benefit from the MDT ecosystem</p>
    <small>Make intelligent, green living accessible.</small>
</div>
<div class="page-ath-form">

    <h2 class="page-ath-heading">{{__('Sign up')}} </h2>

    <form class="register-form validate validate-modern" method="POST" action="{{ route('register') }}" id="register">
        @csrf
        @include('layouts.messages')
        @if(! is_maintenance() && application_installed(true) && ($check_users == 0) )
        <div class="alert alert-info-alt">
            Please register first your Super Admin account with adminstration privilege.
        </div>
        @endif
        <div class="input-item">
            <input type="text" placeholder="{{__('Your Name')}}" class="input-bordered{{ $errors->has('name') ? ' input-error' : '' }}" name="name" value="{{ old('name') }}" minlength="3" required>
        </div>
        <div class="input-item">
            <input type="email" placeholder="{{__('Your Email')}}" class="input-bordered{{ $errors->has('email') ? ' input-error' : '' }}" name="email" value="{{ old('email') }}" required>
        </div>
        <div class="input-item">
            <input type="password" placeholder="{{__('Password')}}" class="input-bordered{{ $errors->has('password') ? ' input-error' : '' }}" name="password" id="password" minlength="6" required>
        </div>
        <div class="input-item">
            <input type="password" placeholder="{{__('Repeat Password')}}" class="input-bordered{{ $errors->has('password_confirmation') ? ' input-error' : '' }}" name="password_confirmation" data-rule-equalTo="#password" minlength="6" required>
        </div>
        @if( gws('referral_info_show')==1 && get_refer_id() )
        <div class="input-item">
            <input type="text" class="input-bordered" value="Your were invited by {{ get_refer_id(true) }}" disabled readonly>
        </div>
        @endif
        
        @if(( application_installed(true)) && ($check_users > 0))
            @if(get_page_link('terms') || get_page_link('policy'))
            <div class="input-item text-left">
                <input name="terms" class="input-checkbox input-checkbox-md" id="agree" type="checkbox" required="required" data-msg-required="{{ __("You should accept our terms and policy.") }}">
                <label for="agree">{!! __("I agree to the MDT's ") . ' ' .get_page_link('terms', ['target'=>'_blank', 'name' => true, 'status' => true]) . ((get_page_link('terms', ['status' => true]) && get_page_link('policy', ['status' => true])) ? ' '.__('and').' ' : '') . get_page_link('policy', ['target'=>'_blank', 'name' => true, 'status' => true]) !!}.</label>
            </div>
            @else
            <div class="input-item text-left">
                <label for="agree">{{__('By registering you agree to the terms and conditions.')}}</label>
            </div>
            @endif
        @else
            <input name="terms" value="1" type="hidden">
        @endif
        <button type="submit" class="btn btn-primary btn-block">{{ ( application_installed(true) && ($check_users == 0) ) ? __('Complete Installation') : __('Create Account') }}</button>
    </form>

    @if(application_installed(true) && ($check_users > 0) && Schema::hasTable('settings'))
        @if (
        (get_setting('site_api_fb_id', env('FB_CLIENT_ID', '')) != '' && get_setting('site_api_fb_secret', env('FB_CLIENT_SECRET', '')) != '') ||
        (get_setting('site_api_google_id', env('GOOGLE_CLIENT_ID', '')) != '' && get_setting('site_api_google_secret', env('GOOGLE_CLIENT_SECRET', '')) != '')
        )
        <div class="sap-text"><span>{{__('Or Sign up with')}}</span></div>
        <ul class="row guttar-20px guttar-vr-20px">
            <li class="col"><a href="{{ route('social.login', 'facebook') }}" class="btn btn-outline btn-dark btn-facebook btn-block"><em class="fab fa-facebook-f"></em><span>{{__('Facebook')}}</span></a></li>
            <li class="col"><a href="{{ route('social.login', 'google') }}" class="btn btn-outline btn-dark btn-google btn-block"><em class="fab fa-google"></em><span>{{__('Google')}}</span></a></li>
        </ul>
        @endif

        <div class="gaps-4x"></div>
        <div class="form-note">
            {{__('Already have an account ?')}} <a href="{{ route('login') }}"> <strong>{{__('Sign in')}}</strong></a>
        </div>
    @endif
</div>
@endsection

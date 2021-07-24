<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="js">
<head>
    <meta charset="utf-8">
    <meta name="apps" content="{{ site_whitelabel('apps') }}">
    <meta name="author" content="{{ site_whitelabel('author') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ site_favicon() }}">
    <title>@yield('title') |  MDT </title>
    <link rel="stylesheet" href="{{ asset(style_theme('vendor')) }}">
    <link rel="stylesheet" href="{{ asset(style_theme('user')) }}">
    @stack('header')
@if(get_setting('site_header_code', false))
    {{ html_string(get_setting('site_header_code')) }}
@endif

<style>
    
   .token-balance-icon img {
       width: 42px;
   }
   .topbar {
       background: #000000 !important;
   }

</style>

</head>
<body>
    <div class="page-content">
        <div class="container">
            <div class="row">
                 <div class="col-12">
                    {!! UserPanel::token_sales_progress('',  ['class' => 'card-full-height']) !!}
                </div>
            </div>
        </div>
</div>






@if(gws('theme_custom'))
    <link rel="stylesheet" href="{{ asset(style_theme('custom')) }}">
@endif
    <script>
        var base_url = "{{ url('/') }}",
        {!! (has_route('transfer:user.send')) ? 'user_token_send = "'.route('transfer:user.send').'",' : '' !!}
        {!! (has_route('withdraw:user.request')) ? 'user_token_withdraw = "'.route('withdraw:user.request').'",' : '' !!}
        {!! (has_route('user.ajax.account.wallet')) ? 'user_wallet_address = "'.route('user.ajax.account.wallet').'",' : '' !!}
        csrf_token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    </script>
    <script src="{{ asset('assets/js/jquery.bundle.js').css_js_ver() }}"></script>
    <script src="{{ asset('assets/js/script.js').css_js_ver() }}"></script>
    <script src="{{ asset('assets/js/app.js').css_js_ver() }}"></script>
    @stack('footer')
    <script type="text/javascript">
        @if (session('resent'))
        show_toast("success","{{ __('A fresh verification link has been sent to your email address.') }}");
        @endif
    </script>
    @if(get_setting('site_footer_code', false))
    {{ html_string(get_setting('site_footer_code')) }}
    @endif

    @yield('tokenCalScript')
</body>
</html>
@extends('layouts.user')
@section('title', __('User Dashboard'))
@php
$has_sidebar = false;
$base_currency = base_currency();
@endphp

@section('content')
<div class="content-area user-account-dashboard">
    @include('layouts.messages')
    <div class="row">
        <div class="col-lg-4">
            {!! UserPanel::user_balance_card($contribution, ['vers' => 'side', 'class'=> 'card-full-height']) !!}
        </div>
        <div class="col-lg-4 col-md-6">
            {!! UserPanel::user_token_block('', ['vers' => 'buy']) !!}
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="account-info card card-full-height">
                <div class="card-innr">
                    {!! UserPanel::user_account_status() !!}
                    <div class="gaps-2x"></div>
                    <div class="user-receive-wallet">
                        <h6 class="card-title card-title-sm">Your Wallet Address :</h6>
                        <div class="gaps-1x"></div>
                        <div class="d-flex justify-content-between" style="font-size:12px;color:#aac9ff;">
                            {{ Auth::user()->walletAddress }}
                           </div>
                        </div>
                  
                </div>
            </div>
        </div>
        @if(get_page('home_top', 'status') == 'active')
        <div class="col-12 col-lg-7">
            <div class="card content-welcome-block card-full-height">
                <div class="card-innr">
                    <div class="row guttar-vr-20px">
                        <div class="col-sm-5 col-md-4">
                            <div class="card-image card-image-sm">
                                <img width="240" src="https://api.qrserver.com/v1/create-qr-code/?size=240x240&data={{ Auth::user()->walletAddress }}" alt="">
                            
                
                            </div>
                        </div>
                        <div class="col-sm-7 col-md-8">
                            <div class="card-content">
                                <h4>Thank you for your interest to our Modern Development Token</h4>You can contribute MDT go through Buy Token page. 

             <br><strong>QR Code shown contains your wallet address.</strong> 
               
               </div>
            </div>
        </div>
        <div class="d-block d-md-none gaps-0-5x mb-0"></div>
    </div>
</div>
        </div>
        <div class="col-12 col-lg-5">
            {!! UserPanel::token_sales_progress('',  ['class' => 'card-full-height']) !!}
        </div>
        @endif

    </div>
</div>
@endsection

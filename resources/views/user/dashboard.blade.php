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
         <div class="col-md-6">
            <div class="card content-welcome-block card-full-height">
             <div class="card-innr">
                <div class="card-content">
                        <h4>Staking MDT</h4>
                                    Staking is a great way to maximize your holdings in MDT that would otherwise be sitting in your MDT account. Once you have staked your assets you can earn staking rewards on top of your holdings and, in applicable MDT.

                                            

                    </div>
             </div>
         </div>

        </div>
        <div class="col-md-6">
            <div class="token-statistics card-token card  card-full-height">
                <div class="card-innr">
                    <div class="token-balance token-balance-with-icon">

                        <div class="token-balance-text">
                            <h6 class="card-sub-title">Total Tokens Staked</h6>
                            <span class="lead">{{number_format($semiannual + $annual) }} <span>MDT <em class="fas fa-lock fs-11"></em></span> <span><a href="/user/account/balance#tokendstake" class="btn btn-xs btn-auto btn-success">View Details</a></span></span>
                        </div>
                    </div>
                    <div class="token-balance token-balance-s2">
                      <h6 class="card-sub-title">Staked Tenure</h6>
                      <ul class="token-balance-list">
                          <li class="token-balance-sub"><span class="lead">{{ number_format($semiannual) }} MDT</span><span class="sub">6 months ( <i>5% APR</i> )</span>
                          </li>
                          <li class="token-balance-sub"><span class="lead">{{ number_format($annual) }} MDT</span><span class="sub">12 months ( <i>11% APR</i> )</span>
                          </li>
                          
                      </ul>
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

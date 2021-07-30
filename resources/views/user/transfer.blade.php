@extends('layouts.user')
@section('title', __('Transfer '))

@section('content')

@php
$has_sidebar = false;
$content_class = 'col-lg-8';

$current_date = time();
$upcoming = is_upcoming();

$_b = 0; 
$bc = base_currency();
$default_method = token_method();
$symbol = token_symbol();
$method = strtolower($default_method);
$min_token = ($minimum) ? $minimum : active_stage()->min_purchase;

$sold_token = (active_stage()->soldout + active_stage()->soldlock);
$have_token = (active_stage()->total_tokens - $sold_token);
$sales_ended = (($sold_token >= active_stage()->total_tokens) || ($have_token < $min_token)) ? true : false;

$is_method = is_method_valid();

$sl_01 = ($is_method) ? '01 ' : '';
$sl_02 = ($sl_01) ? '02 ' : '';
$sl_03 = ($sl_02) ? '03 ' : '';


$exc_rate = (!empty($currencies)) ? json_encode($currencies) : '{}';
$token_price = (!empty($price)) ? json_encode($price) : '{}';
$amount_bonus = (!empty($bonus_amount)) ? json_encode($bonus_amount) : '{1 : 0}';
$decimal_min = (token('decimal_min')) ? token('decimal_min') : 0;
$decimal_max = (token('decimal_max')) ? token('decimal_max') : 0;

@endphp
@include('layouts.messages')
<div class="content-area card">
    <div class="card-innr">
        <form action="javascript:void(0)" method="post" class="token-transaction">
            <div class="card-head"> <h4 class="card-title">TRANSFER TOKEN</h4></div>
            <div class="card-tex">Send token to any user</div>
            <hr>
            <div class="card-head"><h4 class="card-title">Amount to transfer</h4></div>
            <div class="form-group">
                <input type="number" name="transfer-amount" class="form-control">
            </div>
             <div class="card-head"><h4 class="card-title">Transfer To</h4></div>
            <div class="form-group">
                <input type="text" name="transferto" placeholder="Userid / Wallet Address" class="form-control">
            </div>
            <input type="hidden" name="userid">
            <div class="pay-notes">
                    <div class="note note-plane note-light note-md font-italic">
                        <em class="fas fa-info-circle"></em>
                        <p><strong><span class="senttoken">0</span></strong> Tokens will deducted to your account and will be sent to <strong><span class="token-received">User</span></strong> </p>
                    </div>
                </div>
            <div class="pay-buttons">
                <div class="pay-buttons pt-0">
                    <button type="button" id="submitTransfer" class="btn btn-primary  ">Transfer&nbsp;<i class="ti ti-share"></i></button>
                </div> 
            </div>
            
        </form>
    </div>
</div>

@push('sidebar')
<div class="aside sidebar-right col-lg-4">
    @if(!has_wallet() && gws('token_wallet_req')==1 && !empty(token_wallet()))
    <div class="d-none d-lg-block">
        {!! UserPanel::add_wallet_alert() !!}
    </div>
    @endif
    {!! UserPanel::user_balance_card($contribution, ['vers' => 'side']) !!}
    <div class="token-sales card">
        <div class="card-innr">
            <div class="card-head">
                <h5 class="card-title card-title-sm">{{__('Token Sales')}}</h5>
            </div>
            <div class="token-rate-wrap row">
                <div class="token-rate col-md-6 col-lg-12">
                    <span class="card-sub-title">{{ $symbol }} {{__('Token Price')}}</span>
                    <h4 class="font-mid text-dark">1 {{ $symbol }} = <span id="currentokenprice" data-price="{{ to_num($token_prices->$bc, 'max') }}">{{ to_num($token_prices->$bc, 'max') .' '. base_currency(true) }}</span></h4>
                </div>
                <div class="token-rate col-md-6 col-lg-12">
                    <span class="card-sub-title">{{__('Exchange Rate')}}</span>
                    @php
                    $exrpm = collect($pm_currency);
                    $exrpm = $exrpm->forget(base_currency())->take(2);
                    $exc_rate = '<span>1 '.base_currency(true) .' ';
                    foreach ($exrpm as $cur => $name) {
                        if($cur != base_currency() && get_exc_rate($cur) != '') {
                            $exc_rate .= ' = '.to_num(get_exc_rate($cur), 'max') . ' ' . strtoupper($cur);
                        }
                    }
                    $exc_rate .= '</span>';
                    @endphp
                    {!! $exc_rate !!}
                </div>
            </div>
            @if(!empty($active_bonus))
            <div class="token-bonus-current">
                <div class="fake-class">
                    <span class="card-sub-title">{{__('Current Bonus')}}</span>
                    <div class="h3 mb-0">{{ $active_bonus->amount }} %</div>
                </div>
                <div class="token-bonus-date">{{__('End at')}}<br>{{ _date($active_bonus->end_date, get_setting('site_date_format')) }}</div>
            </div>
            @endif
        </div>
    </div>
    {!! UserPanel::token_sales_progress('',  ['class' => 'mb-0']) !!}
</div>{{-- .col.aside --}}
@endpush

@endsection



@section('tokenCalScript')
<script>
    
  $(function(){
     var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');

    $('[name=transfer-amount]').on('keyup',function() {
            $('.senttoken').html($(this).val());
    });

    $('[name=transferto]').on('keyup',function() {
            $.ajax({
                
                    url: '/user/search-user',
                    type: 'POST',
                    data: {_token: CSRF_TOKEN, transferto:$(this).val()},
                    dataType: 'JSON',
                    /* remind that 'data' is the response of the AjaxController */
                    success: function (data) { 
                            if(data.userid > 1) {
                                $('[name=userid]').val(data.userid);
                                $('.token-received').html('(<i>'  + data.email+ '</i>) - ' +  data.name);
                            }
                            else {
                                    $('.token-received').html('Unknown User');
                                    //show_toast("error","{{ __('Unknown User.') }}");
                            }
                    }
                }); 
    });


    $('#submitTransfer').on('click',function() {
          var amount  = $('[name=transfer-amount]').val();
          var recepient =  $('[name=userid]').val();
            $.ajax({
                    url: '/user/submit-transfer',
                    type: 'POST',
                    data: {_token: CSRF_TOKEN, amount : amount , recepient : recepient },
                    dataType: 'JSON',
            
                    success: function (data) { 
                       

                            show_toast(data.message_type,data.message);

                             if(data.message_type == 'success') {
                                    setTimeout(function(){
                                        window.location.href = "/user/transfer";
                                    },1000)
                             }
                            
                    }
                }); 
    });
        
  });

</script>

@endsection
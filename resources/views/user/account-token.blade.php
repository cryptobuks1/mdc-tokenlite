@extends('layouts.user')
@section('title', __(':symbol  Balance', ['symbol' => token_symbol()]))
@php
$has_sidebar = true;
$symbol = token_symbol();
$base_symbol = base_currency(1);
$TAC = $token_account;
$TST = $token_stages;
// dd($TST);
@endphp

@section('customstyle')
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.min.css">
@endsection
@section('content')
<style type="text/css">
    html {
  scroll-behavior: smooth;
}
</style>
<div class="content-area card">
    <div class="card-innr">
        @include('layouts.messages')
        <div class="card-head">
            <h4 class="card-title">{{ __('My :symbol ', ['symbol'=> $symbol]) }}</h4>
        </div>
        <div class="gaps-1x"></div>

        <div class="card-bordered nopd">
            <div class="card-innr">
                <div class="row guttar-vr-15px align-items-center">
                    <div class="col-md-8">
                        <div class="total-block">
                            <h6 class="total-title ucap">{{ __('Token Balance') }}</h6>
                            <span class="total-amount-lead">{{ to_num_token($TAC->current ,'max').' '.$symbol }}
                                @if($TAC->pending > 0)
                                <em class="align-middle fas fa-info-circle fs-12 text-light" data-toggle="tooltip" data-placement="right" title="{{ __('+:amount :symbol Pending Request', ['amount' => to_num_token($TAC->pending), 'symbol' => $symbol]) }}"></em>
                                @endif
                            </span>
                            <p class="total-note">{{ __('Equivalent to ') }}<span>{{ to_num($TAC->current_in_base, 'max').' '.$base_symbol }}</span></p>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-right">

                        <a href="{{ route('user.token') }}" class="btn btn-primary">{{ __('Buy More Token') }}</a>
                            <hr>
                         <a href="{{ route('user.transfer') }}" class="btn btn-primary">{{ __('Transfer Token') }}</a>
                        
                    </div>
                </div>
            </div>
            <div class="sap sap-light"></div>
            <div class="card-innr">
                <div class="total-block">
                    <h5 class="total-title-sm">{{ __('Total Tokens') }}</h5>
                    <span class="total-amount">{{ to_num_token($TAC->total ).' '.$symbol }}</span>
                </div>
                <div class="total-block">
                    <ul class="list total-wg">
                        <li>
                            <span class="total-title-xs">{{ __('Purchased Token') }}</span>
                            <span class="total-amount-sm">{{ to_num_token($TAC->purchased).' '.$symbol }}</span>
                        </li>
                        <li>
                            <span class="total-title-xs">{{ __('Referral Token') }}</span>
                            <span class="total-amount-sm">{{ to_num_token($TAC->referral).' '.$symbol }}</span>
                        </li>
                        <li>
                            <span class="total-title-xs">{{ __('Bonuses Token') }}</span>
                            <span class="total-amount-sm">{{ to_num_token($TAC->bonuses).' '.$symbol }}</span>
                        </li>
                    </ul>
                    @if($TAC->has_withdraw || $TAC->has_transfer)
                    <ul class="list total-wg">
                        @if($TAC->has_withdraw)
                        <li>
                            <span class="total-title-xs">{{ __('Withdraw Token') }}</span>
                            <span class="total-amount-sm">{{ to_num_token($TAC->withdraw).' '.$symbol }}</span>
                        </li>
                        @endif
                        @if($TAC->has_transfer)
                        <li>
                            <span class="total-title-xs">{{ __('Transferred Token') }}</span>
                            <span class="total-amount-sm">{{ to_num_token($TAC->transfer).' '.$symbol }}</span>
                        </li>
                        @endif
                    </ul>
                    @endif
                </div>
            </div>
            <div class="sap sap-light"></div>
            <div class="card-innr">
                <div class="total-block">
                    <h5 class="total-title-sm">{{ __('Total Contributed') }}</h5>
                    <span class="total-amount">{{ to_num($TAC->contributed, 'max').' '.$base_symbol }}</span>
                </div>
                @if(!empty($TAC->contribute_in))
                <div class="total-block total-block-lg">
                    <h6 class="total-title-xs ucap">{{ __('In Currency') }}</h6>
                    <ul class="list total-wg">
                        @foreach($TAC->contribute_in as $cur => $amt)
                        <li>
                            <span class="total-amount-sm">{{ to_num($amt, 'max') }}</span>
                            <span class="total-title-xs">{{ strtoupper($cur) }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
            @foreach($user_modules as $opt)
            @if(!empty($opt) && $opt->enable==1)
            <div class="sap sap-light"></div>
            <div class="card-innr">
                <div class="row guttar-vr-15px align-items-center">
                    <div class="col-md-8">
                        <div class="text-block mgmb-0-5x">
                            <h6 class="text-title-xs">{{ $opt->title }}</h6>
                            <p class="small">{{ $opt->desc }}</p>
                            @if(!empty($opt->view_route) && $opt->view)
                            <a href="{{ route($opt->view_route) }}" class="link link-primary{{ $opt->view_class }}"><span>{{ $opt->view }}</span> <em class="icon fa fa-angle-right"></em></a>
                            @endif
                        </div>
                    </div>
                    @if($opt->cta)
                    <div class="col-md-4 text-md-right">
                        <a href="{{ (!empty($opt->cta_route) && has_route($opt->cta_route)) ? route($opt->cta_route) : 'javascript:void(0)' }}" class="btn btn-primary btn-sm btn-sm-min{{ $opt->cta_class }}"{!! (($opt->cta_daction) ? ' data-action="'.$opt->cta_daction.'"' : '').(($opt->cta_dtype) ? ' data-type="'.$opt->cta_dtype.'"' : '') !!}>{{ $opt->cta }}</a>
                    </div>
                    @endif
                </div>
                @if($opt->status==1 && $opt->message) 
                    <p class="fs-11 text-danger mt-3"><em>{{ $opt->message }}</em></p>
                @endif
            </div>
            @endif
            @endforeach
        </div>
    </div>{{-- .card-innr --}}
</div>{{-- .card --}}

@if(gws('user_mytoken_stage')==1 && !empty($TST))
<div class="content-area card">
    <div class="card-innr card-innr-fix-x">
        <div class="row guttar-25px guttar-vr-25px">
            @foreach ($TST as $ST)
            <div class="col-md-6">
                <div class="card-bordered nopd">
                    <div class="card-innr">
                        <div class="total-block">
                            <h6 class="total-title-xs ucap">{{ __('Stage Name') }}</h6>
                            <span class="total-title-lead">{{ $ST->name }}</span>
                        </div>
                        <div class="total-block total-block-md">
                            <h6 class="total-title-xs">{{ __('Total Token') }}</h6>
                            <span class="total-amount-lg">{{ to_num_token($ST->token).' '.$symbol }}</span>
                        </div>
                    </div>
                    <div class="sap sap-light"></div>
                    <div class="card-innr">
                        <div class="total-block">
                            <h6 class="total-title-xs">{{ __('Purchased Token') }}</h6>
                            <span class="total-amount">{{ to_num_token($ST->purchase).' '.$symbol }}</span>
                        </div>
                        <div class="total-block">
                            <ul class="list list-col2x guttar-vr-16px">
                                <li>
                                    <span class="total-title-xs">{{ __('Bonus') }}</span>
                                    <span class="total-amount-sm">{{ to_num_token($ST->bonus).' '.$symbol }}</span>
                                </li>
                                <li>
                                    <span class="total-title-xs">{{ __('Referral') }}</span>
                                    <span class="total-amount-sm">{{ to_num_token($ST->referral).' '.$symbol }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="sap sap-light"></div>
                    <div class="card-innr">
                        <div class="has-action">
                            <div class="total-block">
                                <h6 class="total-title-xs">{{ __('Total Contributed') }}</h6>
                                <span class="total-amount">{{ to_num($ST->contribute, 'max').' '.$base_symbol }}</span>
                            </div>
                            @if(!empty($ST->contribute_in))
                            <div class="total-action">
                                <a href="#" class="toggle-tigger"><em class="ti ti-more-alt"></em></a>
                                <div class="toggle-class dropdown-content dropdown-content-md dropdown-content-up-left">
                                    <div class="total-block">
                                        <h6 class="total-title-xs ucap mb-2">{{ __('In Currency') }}</h6>
                                        <ul class="list guttar-vr-12px">
                                            @foreach($ST->contribute_in as $cur => $amt)
                                            <li>
                                                <span class="total-amount-sm">{{ to_num($amt, 'max') }}</span>
                                                <span class="total-title-xs">{{ strtoupper($cur) }}</span>
                                            </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>{{-- .card --}}
@endif
<div class="content-area card" id="tokendstake">
    <div class="card-innr card-innr-fix-x">
        <div class="card-head">
            <h4 class="card-title">TOKENS STAKED</h4>
        </div>
         <div class="card-bordered ">
            <table class="table" id="stakedList">
                <thead>
                    <tr>
                        <th>Token Staked</th>
                        <th>Staked Tenure</th>
                        <th>APR</th>
                        <th>Date Token Staked</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tokenstaked as $staked)
                    <tr>
                        <td>{{ number_format($staked->token_staked)}}</td>
                        <td>{{ ($staked->staking_tenure == 'annual') ? '12 months' : '6 months'  }}</td>
                        <td>{{ ($staked->staking_tenure == 'annual') ? '12%' : '5%'  }}</td>
                        <td>{{ date('F d, Y',strtotime($staked->date_staked)) }}</td>
                        <td><?php $status= ($staked->status == 1) ? 'active' : 'inactive' ?>
                            <a href="javascript:void(0)" class="btn btn-xs btn-auto <?=($status == 'active') ? 'btn-success' :'btn-danger' ?>">{{$status}}</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
             
         </div>

    </div>
    
</div>
@endsection

@section('tokenCalScript')
<script type="text/javascript" src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
<script type="text/javascript">
    $(function() {
    $('#stakedList').DataTable();
    var hash = location.hash.replace('#', '');
    if(hash === 'tokendstake') {
      $('html, body').animate({
          scrollTop: $('#tokendstake').offset().top
      }, 500);
    }
});
</script>

@endsection
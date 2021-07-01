@extends('layouts.user')
@section('title', __('User Transactions'))

@section('content')

<div class="page-content">
    <div class="container">
        @include('layouts.messages')
        @include('vendor.notice')
        <div class="card content-area content-area-mh" id="crad-admin" style="background-color:#ffffff;">
            <div class="card-innr">
                <div class="card-head has-aside">
                    <h4 class="card-title" style="color:#455e84 !important;">My Referrals </h4>
                    {{-- <div class="relative d-inline-block d-md-none">
                        <a href="#" class="btn btn-light-alt btn-xs btn-icon toggle-tigger"><em class="ti ti-more-alt"></em></a>
                        <div class="toggle-class dropdown-content dropdown-content-center-left pd-2x">
                            <div class="card-opt data-action-list">
                                <ul class="btn-grp btn-grp-block guttar-20px guttar-vr-10px">
                                    <li><a class="btn btn-auto btn-info btn-outline btn-sm" href="{{ route('admin.users.wallet.change') }}">Wallet Change Request</a></li>
                                    <li>
                                        <a href="#" class="btn btn-auto btn-sm btn-primary" data-toggle="modal" data-target="#addUser">
                                            <em class="fas fa-plus-circle"> </em>
                                            <span>Add <span class="d-none d-md-inline-block">User</span></span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div> --}}
                    {{-- <div class="card-opt data-action-list d-none d-md-inline-flex">
                        <ul class="btn-grp btn-grp-block guttar-20px">
                            <li><a class="btn btn-info btn-outline btn-sm" href="{{ route('admin.users.wallet.change') }}">Wallet Change Request</a></li>
                            <li>
                                <a href="#" class="btn btn-auto btn-sm btn-primary" data-toggle="modal" data-target="#addUser">
                                    <em class="fas fa-plus-circle"> </em><span>Add <span class="d-none d-md-inline-block">User</span></span>
                                </a>
                            </li>
                        </ul>
                    </div> --}}
                </div>

                <div class="page-nav-wrap">
                    <div class="page-nav-bar justify-content-between bg-lighter">
                    
                        <div class="search flex-grow-1 pl-lg-4 w-100 w-sm-auto">
                            <form action="{{ route('user.referrals') }}" method="GET" autocomplete="off">
                                <div class="input-wrap">
                                    <span class="input-icon input-icon-left"><em class="ti ti-search"></em></span>
                                    <input type="search" class="input-solid input-transparent" style="color:#455e84 !important;" placeholder="Quick search with name/email/id" value="{{ request()->get('s', '') }}" name="s">
                                </div>
                            </form>
                        </div>
                        
              
            

                @if($users->total() > 0) 
                <table class="data-table user-list">
                    <thead>
                        <tr class="data-item data-head">
                            <th class="data-col data-col-wd-md filter-data dt-user">User</th>
                            <th class="data-col data-col-wd-md dt-email">Email</th>
                            <th class="data-col data-col-wd-md">Level</th>
                            <th class="data-col dt-token">Tokens</th>
                            
                          
                            <th class="data-col dt-login">Date Registered</th>
                            <th class="data-col dt-status">Status</th>
                            
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                        <tr class="data-item">
                            <td class="data-col data-col-wd-md dt-user">
                                <div class="d-flex align-items-center">
                                    <div class="fake-class">
                                        <span class="lead user-name text-wrap">{{ $user->name }}</span>
                                        <span class="sub user-id">{{ set_id($user->id, 'user') }}
                                            @if($user->role == 'admin') 
                                            <span class="badge badge-xs badge-dim badge-{{($user->type != 'demo')?'success':'danger'}}">ADMIN</span>
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="data-col data-col-wd-md dt-email">
                                <span class="sub sub-s2 sub-email text-wrap">{{ explode_user_for_demo($user->email, auth()->user()->type ) }}</span>
                            </td>
                            <td> {{ $user->level }}</td>

                            <td class="data-col dt-token">
                                <span class="lead lead-btoken">{{ number_format($user->tokenBalance) }}</span>
                            </td>
                           
                            <td class="data-col dt-login">
                                <span class="sub sub-s2 sub-time">{{  _date($user->created_at) }}</span>
                            </td>
                            <td class="data-col dt-status">
                                <span class="dt-status-md badge badge-outline badge-md badge-{{ __status($user->status,'status') }}">{{ __status($user->status,'text') }}</span>
                                <span class="dt-status-sm badge badge-sq badge-outline badge-md badge-{{ __status($user->status,'status') }}">{{ substr(__status($user->status,'text'), 0, 1) }}</span>
                            </td>
                          
                        </tr>
                        {{-- .data-item --}}
                        @endforeach
                    </tbody>
                </table>
                @else 
                    <div class="bg-light text-center rounded pdt-5x pdb-5x" style="text-align:center !important;">
                        <p  style="color:#455e84 !important;"><em class="ti ti-server fs-24"></em><br>{{ ($is_page=='all') ? 'No investor / user found!' : 'No '.$is_page.' user here!' }}</p>
                        <p  style="color:#455e84 !important;"><a class="btn btn-primary btn-auto" href="{{ route('user.referrals') }}">View All Users</a></p>
                    </div>
                @endif

                @if ($pagi->hasPages())
                <div class="pagination-bar">
                    <div class="d-flex flex-wrap justify-content-between guttar-vr-20px guttar-20px">
                        <div class="fake-class">
                            <ul class="btn-grp guttar-10px pagination-btn">
                                @if($pagi->previousPageUrl())
                                <li><a href="{{ $pagi->previousPageUrl() }}" class="btn ucap btn-auto btn-sm btn-light-alt">Prev</a></li>
                                @endif 
                                @if($pagi->nextPageUrl())
                                <li><a href="{{ $pagi->nextPageUrl() }}" class="btn ucap btn-auto btn-sm btn-light-alt">Next</a></li>
                                @endif
                            </ul>
                        </div>
                        <div class="fake-class">
                            <div class="pagination-info guttar-10px justify-content-sm-end justify-content-mb-end">
                                <div class="pagination-info-text ucap">Page </div>
                                <div class="input-wrap w-80px">
                                    <select class="select select-xs select-bordered goto-page" data-dd-class="search-{{ ($pagi->lastPage() > 7) ? 'on' : 'off' }}">
                                        @for ($i = 1; $i <= $pagi->lastPage(); $i++)
                                        <option value="{{ $pagi->url($i) }}"{{ ($pagi->currentPage() ==$i) ? ' selected' : '' }}>{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                            <div class="pagination-info-text ucap">of {{ $pagi->lastPage() }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
            {{-- .card-innr --}}
        </div>{{-- .card --}}
    </div>{{-- .container --}}
</div>{{-- .page-content --}}

@endsection

@section('modals')

<div class="modal fade" id="addUser" tabindex="-1">
    <div class="modal-dialog modal-dialog-md modal-dialog-centered">
        <div class="modal-content">
            <a href="#" class="modal-close" data-dismiss="modal" aria-label="Close"><em class="ti ti-close"></em></a>
            <div class="popup-body popup-body-md">
                <h3 class="popup-title">Add New User</h3>
                <form action="{{ route('admin.ajax.users.add') }}" method="POST" class="adduser-form validate-modern" id="addUserForm" autocomplete="false">
                    @csrf
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="input-item input-with-label">
                                <label class="input-item-label">User Type</label>
                                <select name="role" class="select select-bordered select-block" required="required">
                                    <option value="user">
                                        Regular
                                    </option>
                                    <option value="admin">
                                        Admin
                                    </option>

                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="input-item input-with-label">
                        <label class="input-item-label">Full Name</label>
                        <div class="input-wrap">
                            <input name="name" class="input-bordered" minlength="3" required="required" type="text" placeholder="User Full Name">
                        </div>

                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="input-item input-with-label">
                                <label class="input-item-label">Email Address</label>
                                <div class="input-wrap">
                                    <input class="input-bordered" required="required" name="email" type="email" placeholder="Email address">
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="input-item input-with-label">
                                <label class="input-item-label">Password</label>
                                <div class="input-wrap">
                                    <input name="password" class="input-bordered" minlength="6" placeholder="Automatically generated if blank" type="password" autocomplete='new-password'>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="input-item">
                        <input checked class="input-checkbox input-checkbox-sm" name="email_req" id="send-email" type="checkbox">
                        <label for="send-email">Required Email Verification
                        </label>
                    </div>
                    <div class="gaps-1x"></div>
                    <button class="btn btn-md btn-primary" type="submit">Add User</button>
                </form>
            </div>
        </div>
        {{-- .modal-content --}}
    </div>
    {{-- .modal-dialog --}}
</div>

<div class="modal fade" id="EmailUser" tabindex="-1">
    <div class="modal-dialog modal-dialog-md modal-dialog-centered">
        <div class="modal-content">
            <a href="#" class="modal-close" data-dismiss="modal" aria-label="Close"><em class="ti ti-close"></em></a>
            <div class="popup-body popup-body-md">
                <h3 class="popup-title">Send Email to User </h3>
                <div class="msg-box"></div>
                <form class="validate-modern" id="emailToUser" action="{{ route('admin.ajax.users.email') }}" method="POST" autocomplete="off">
                    @csrf
                    <input type="hidden" name="user_id" id="user_id">
                    <div class="input-item input-with-label">
                        <label class="clear input-item-label">Email Subject</label>
                        <div class="input-wrap">
                            <input type="text" name="subject" class="input-bordered cls" placeholder="New Message">
                        </div>
                    </div>
                    <div class="input-item input-with-label">
                        <label class="clear input-item-label">Email Greeting</label>
                        <div class="input-wrap">
                            <input type="text" name="greeting" class="input-bordered cls" placeholder="Hello User">
                        </div>
                    </div>
                    <div class="input-item input-with-label">
                        <label class="clear input-item-label">Your Message</label>
                        <div class="input-wrap">
                            <textarea required="required" name="message" class="input-bordered cls input-textarea input-textarea-sm" type="text" placeholder="Write something..."></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Send Email</button>
                </form>
            </div>
        </div>{{-- .modal-content --}}
    </div>{{-- .modal-dialog --}}
</div>

@endsection
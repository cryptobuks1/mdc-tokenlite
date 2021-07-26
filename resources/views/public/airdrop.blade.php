<!doctype html>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>MDT TOKEN AIRDROP</title>
 <meta name="description" content="Power the vision, benefit from the MDT ecosystem.Make intelligent, green living accessible.">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://app.moderntoken.io/airdrop">
<meta property="og:title" content="MDT TOKEN AIRDROP">
<meta property="og:description" content="Power the vision, benefit from the MDT ecosystem.Make intelligent, green living accessible.">
<meta property="og:image" content="https://static.wixstatic.com/media/9d58af_47cf0ec976554dc6b95b5eb4d93271f1~mv2.png/v1/crop/x_60,y_360,w_1020,h_372/fill/w_340,h_124,al_c,q_85,usm_0.66_1.00_0.01/MDT%20Logo%20hor.webp">

<!-- Twitter -->
<meta property="twitter:card" content="summary_large_image">
<meta property="twitter:url" content="https://app.moderntoken.io/airdrop">
<meta property="twitter:title" content="MDT TOKEN AIRDROP">
<meta property="twitter:description" content="Power the vision, benefit from the MDT ecosystem.Make intelligent, green living accessible.">
<meta property="twitter:image" content="https://static.wixstatic.com/media/9d58af_47cf0ec976554dc6b95b5eb4d93271f1~mv2.png/v1/crop/x_60,y_360,w_1020,h_372/fill/w_340,h_124,al_c,q_85,usm_0.66_1.00_0.01/MDT%20Logo%20hor.webp">
<link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/airdrop.css')}}">
<link rel="shortcut icon" href="https://app.moderntoken.io/assets/images/logo.png" />
</head>
<body>
  <div class="metamask-wrapper">
            <img src="https://docs.metamask.io/metamask-fox.svg" alt="MetaMask Docs" class="logo">
             <a href="https://metamask.io/download.html" target="_blank" class="metamask-install">Install Metamask</a>
  </div>
  <div class="lds-dual-ring"></div>

<div class="wrapper">
      <a title="MDT" href="https://moderntoken.io" aria-current="page" class="hero-logo-mini mc hide-mobile w-inline-block w--current"><div>MDT</div></a>
      <a href="#" class="wallet-connect disconnected"><img src="https://docs.metamask.io/metamask-fox.svg" alt="MetaMask Docs" class="logo"> <span class="wallet-status">Connect Wallet</span></a>

           <div class="hero beta">
        <div class="hero-jumbo beta w-container">
          <a title="MDT" href="" aria-current="page" class="hero-logo w-inline-block w--current">
          </a>
          <div class="hero-title xl">
            <!-- <img src="TextBG.png" class="text-bg-img" /> -->
            <!-- <strong class="kp-label">ElonDoge</strong> -->
            <strong id="js-hero-line" class="inverse ">MDT </strong>
          </div>
          <p style="
              opacity: 1;
              transform: translate3d(0px, 0px, 0px) scale3d(1, 1, 1)
                rotateX(0deg) rotateY(0deg) rotateZ(0deg) skew(0deg, 0deg);
              transform-style: preserve-3d;
            " class="p-hero txt-dark">
              Power the vision, benefit from the MDT ecosystem.<br>
              <small>Make intelligent, green living accessible.</small>
          </p>

          <div class="tags-top">
            <strong class="tag-top">A total of 25M Tokens will be given away.</strong>
            <strong class="tag-top">Be a part of MDT Ecosystem.</strong>
            <strong class="tag-top">Start by getting a FREE 500 tokens.</strong>
            
          </div>
          <div class="claim-token ">
              <a class="btn claimbtn" >
                CLAIM FREE TOKENS
              </a>

            </div>
            <a class="thash" href="https://bscscan.com/tx" target="_blank"   style="margin-top: 20px;">Transaction Hash :  </a>
            <small style="margin-top: 20px;">Note : You can only claim one time per wallet address. If you have claimed free tokens, please don't try claiming using same wallet address, you won't be receiving tokens and gas fee won't be refunded .</small>
        </div>

        <div class="bg-gradient"></div>
        <div style="opacity: 1" class="fixed-topbg onscroll"></div>
      </div>
  
  </div>

<script src="https://cdn.jsdelivr.net/npm/web3@0.20.7/dist/web3.min.js" integrity="sha256-QBLUDyDq82B1cnZjAMn0hPdTo4Juu1S7/U1dgf22x8I=" crossorigin="anonymous"></script>
<script src="https://unpkg.com/@metamask/detect-provider/dist/detect-provider.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

  <script type="text/javascript">
     
    $(function() {

      var abi = [{"constant":false,"inputs":[{"name":"amount","type":"uint256"}],"name":"claimTokens","outputs":[{"name":"","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":false,"inputs":[{"name":"_maxAllowanceInclusive","type":"uint256"}],"name":"setMaxAllowance","outputs":[],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":false,"inputs":[{"name":"isPaused","type":"bool"}],"name":"setPause","outputs":[],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":false,"inputs":[],"name":"widthrawTokens","outputs":[{"name":"","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"inputs":[{"name":"_erc20ContractAddress","type":"address"},{"name":"_maxAllowanceInclusive","type":"uint256"}],"payable":false,"stateMutability":"nonpayable","type":"constructor"},{"anonymous":false,"inputs":[{"indexed":false,"name":"requestor","type":"address"},{"indexed":false,"name":"amount","type":"uint256"}],"name":"GetTokens","type":"event"},{"anonymous":false,"inputs":[{"indexed":false,"name":"owner","type":"address"},{"indexed":false,"name":"tokenAmount","type":"uint256"}],"name":"ReclaimTokens","type":"event"},{"anonymous":false,"inputs":[{"indexed":false,"name":"setter","type":"address"},{"indexed":false,"name":"newState","type":"bool"},{"indexed":false,"name":"oldState","type":"bool"}],"name":"SetPause","type":"event"},{"anonymous":false,"inputs":[{"indexed":false,"name":"setter","type":"address"},{"indexed":false,"name":"newState","type":"uint256"},{"indexed":false,"name":"oldState","type":"uint256"}],"name":"SetMaxAllowance","type":"event"},{"constant":true,"inputs":[{"name":"","type":"address"}],"name":"claimedTokens","outputs":[{"name":"","type":"uint256"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":true,"inputs":[],"name":"erc20Contract","outputs":[{"name":"","type":"address"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":true,"inputs":[],"name":"maxAllowanceInclusive","outputs":[{"name":"","type":"uint256"}],"payable":false,"stateMutability":"view","type":"function"}];
         var deployedAddress = "0x2C10fE5D2d53E17BB4F53e56c27d47D62B62821b"  ;

          const provider =  detectEthereumProvider();

            if (provider) {
            
            } else {
               console.log('Please install MetaMask!');
            }
        if (typeof window.ethereum !== 'undefined') {
              console.log('MetaMask is installed!');
              ethereum.request({ method: 'eth_requestAccounts' });
              $('.claimbtn').addClass('connected');
              $('.wallet-connect').addClass('connected-wallet');
               $('.wallet-connect').removeClass('disconnected');
              $('.wallet-status').html('Connected');
              $('.lds-dual-ring').hide();
          }
          else {
              alert('METAMASK WALLET IS REQUIRED');
              $('.metamask-wrapper').css('display','flex');
          }

         if(ethereum.isConnected()) {
              $('.claimbtn').removeClass('disconnected');
              $('.claimbtn').addClass('connected');
          } 
        else {

              $('.claimbtn').removeClass('connected');
              $('.claimbtn').addClass('disconnected');
              $('.wallet-connect').addClass('disconnected');
              $('.wallet-connect').removeClass('connected-wallet');
        }    
        const ethereumButton = document.querySelector('.claimbtn');

        $('.claimbtn').click(function() {
                // if($(this).hasClass('connected')) {
                    const amountToClaim = 500000000000000000000; // 500 MDT
                    window.web3 = new Web3(web3.currentProvider);
                    const contract = web3.eth.contract(abi).at(deployedAddress);
                      
                       web3.eth.defaultAccount=web3.eth.accounts[0];
                       console.log(web3.eth.accounts);
                      contract.claimTokens(amountToClaim,function (err, success) {
                      
                      if(success) {
                          $('.thash').attr('href','https://bscscan.com/tx/'+success);
                          $('.thash').html('Transaction hash : '+ success);
                          $('.thash').fadeIn();
                          $('.claimbtn').html('Claimed');
                          $('.claimbtn').attr('disabled',true);
                          $('.claimbtn').removeClass('connected');
                          $('.claimbtn').css({ 'background-color' : '#f6fba2','background-image' :  'linear-gradient(315deg, #f6fba2 0%, #20ded3 74%)' });
                      }
                   });
              // }
              // else {
              //      //alert('Please connect MetaMask');
              // }
            
          });
       
      });

  </script>
  </body>
</html>
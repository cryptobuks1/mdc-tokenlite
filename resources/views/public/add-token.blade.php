<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title></title>
</head>
<body>


<script type="text/javascript">
	const tokenAddress = '0xbb63d4107b2d37879ae2108f1bf975c6b5ec29a2';
                  const tokenSymbol = 'MDT';
                  const tokenDecimals = 18;
                  const tokenImage = 'https://app.moderntoken.io/assets/images/logo.png';

                  try {
                    // wasAdded is a boolean. Like any RPC method, an error may be thrown.
                    const wasAdded =  ethereum.request({
                      method: 'wallet_watchAsset',
                      params: {
                        type: 'ERC20', // Initially only supports ERC20, but eventually more!
                        options: {
                          address: tokenAddress, // The address that the token is at.
                          symbol: tokenSymbol, // A ticker symbol or shorthand, up to 5 chars.
                          decimals: tokenDecimals, // The number of decimals in the token
                          image: tokenImage, // A string url of the token logo
                        },
                      },
                    });

                    if (wasAdded) {
                       alert('Token info Added, you may now close this window');
                    } else {
                        alert('Failed ! Please reload the page');
                    }
                  } catch (error) {
                    console.log(error);
                  }
</script>
</body>
</html>
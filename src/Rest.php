<?php

namespace Blinktrade;

/**
 * Rest
 *
 * @param {number}  $broker_id
 * @param {string}  $key
 * @param {string}  $secret
 * @param {boolean} $env
 * @param {string}  $version
 */
class Rest
{
    private $broker_id;
    private $key;
    private $secret;
    private $url;
    private $version;

    function __construct($broker_id, $key, $secret, $env = false, $version = 'v1')
    {
        if ($env) {
            $url = 'https://api.testnet.blinktrade.com';
        } else {
            $url = 'https://api.blinktrade.com';
        }

        $this->broker_id = $broker_id;
        $this->key = $key;
        $this->secret = $secret;
        $this->url = $url;
        $this->version = $version;
    }

    public function send($array)
    {
        $key = $this->key;
        $secret = $this->secret;
        $url = $this->url;
        $version = $this->version;

        $nonce = strval(time());
        $signature = hash_hmac('sha256', $nonce, $secret);

        $api = $url . '/tapi/' . $version . '/message';

        $data = $array;
        $data_string = json_encode($data);

        $ch = curl_init($api);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Nonce: ' . $nonce,                       # The nonce must be an integer, always greater than the previous one.
            'APIKey: ' . $key,                        # Your APIKey
            'Content-Type: application/json',         # You must POST a JSON message
            'Signature: ' . $signature,               # Use the API Secret  to sign the nonce using HMAC_SHA256 algo
            'user-agent: blinktrade_tools/0.1',
        ]);

        $result = curl_exec($ch);

        echo $result;
    }

    public function balance()
    {
        $array = [
            "MsgType" => "U2",
            "BalanceReqID" => 1,
        ];

        $this->send($array);
    }

    public function openOrders($page = 0, $page_size = 100)
    {
        $client_order_id = strval(time());

        $array = [
            "MsgType" => "U4",
            "OrdersReqID" => $client_order_id,
            "Page" => $page,
            "PageSize" => $page_size,
            "Filter" => ["has_leaves_qty eq 1"],
        ];

        $this->send($array);
    }

    public function executedOrders($page = 0, $page_size = 100)
    {
        $client_order_id = strval(time());

        $array = [
            "MsgType" => "U4",
            "OrdersReqID" => $client_order_id,
            "Page" => $page,
            "PageSize" => $page_size,
            "Filter" => ["has_cum_qty eq 1"],
        ];

        $this->send($array);
    }

    public function cancelledOrders($page = 0, $page_size = 100)
    {
        $client_order_id = strval(time());

        $array = [
            "MsgType" => "U4",
            "OrdersReqID" => $client_order_id,
            "Page" => $page,
            "PageSize" => $page_size,
            "Filter" => ["has_cxl_qty eq 1"],        ];

        $this->send($array);
    }

    public function newOrder($symbol, $side, $orderType, $price, $orderQty)
    {
        $broker_id = $this->broker_id;
        $client_order_id = strval(time());

        $array = [
            "MsgType" => "D",              # New Order Single message. Check for a full doc here: http://www.onixs.biz/fix-dictionary/4.4/msgType_D_68.html
            "ClOrdID" => $client_order_id, # Unique identifier for Order as assigned by you
            "Symbol" => $symbol,           # Can be BTCBRL, BTCPKR, BTCVND, BTCVEF, BTCCLP.
            "Side" => $side,               # 1 - Buy, 2-Sell
            "OrdType" => $orderType,       # 2 - Limited order
            "Price" => $price,             # Price in satoshis
            "OrderQty" => $orderQty,       # Qty in saothis
            "BrokerID" => $broker_id       # 1=SurBitcoin, 3=VBTC, 4=FoxBit, 5=Tests , 8=UrduBit, 9=ChileBit
        ];

        $this->send($array);
    }

    public function cancelOrder($client_order_id)
    {
        $array = [
            "MsgType" => "F",                  # Order Cancel Request message. Check for a full doc here: http://www.onixs.biz/fix-dictionary/4.4/msgType_F_70.html
            "ClOrdID" => $client_order_id      # Unique identifier for Order as assigned by you
        ];

        $this->send($array);
    }

    public function newAddress()
    {
        $broker_id = $this->broker_id;
        $client_id = strval(time());

        $array = [
            "MsgType" => "U18",             # Deposit request
            "DepositReqID" => $client_id,   # Deposit Request ID.
            "Currency" => "BTC",            # Currency.
            "BrokerID" => $broker_id        # Exchange ID
        ];

        $this->send($array);
    }

    public function FIATDeposit($value)
    {
        $broker_id = $this->broker_id;

        $array = [
            "MsgType" => "U18",       # Deposit request
            "DepositReqID" => 1,      # Deposit Request ID.
            "DepositMethodID" => 403, # Deposit Method ID - Check with your exchange.
            "Value" => $value * 1e8,  # Amount in satoshis
            "Currency" =>  "BRL",     # Currency.
            "BrokerID" => $broker_id  # Exchange ID
        ];

        $this->send($array);
    }

    public function bitcoinWithdrawal($amount, $address)
    {
        $client_id = strval(time());

        $array = [
            "MsgType" => 'U6',
            "WithdrawReqID" => $client_id,  # Request ID.
            "Method" => 'bitcoin',          # bitcoin for BTC. Check with the exchange all available withdrawal methods
            "Amount" => $amount * 1e8,      # Amount in satoshis
            "Currency" => 'BTC',            # Currency
            "Data" => [
                "Wallet" => $address        # Your Wallet
            ],
        ];

        $this->send($array);
    }

    public function FIATWithdrawal($method, $currency, $amount, $data = [])
    {
        $client_id = strval(time());

        $array = [
            "MsgType" => 'U6',
            "WithdrawReqID" => $client_id,
            "Method" => $method,
            "Currency" => $currency,
            "Amount" => $amount * 1e8,
            "Data" => $data,
        ];

        $this->send($array);
    }

    public function listWithdrawals($status, $page = 0, $page_size = 100)
    {
        $client_id = strval(time());

        $array = [
            'MsgType' => 'U26',
            'WithdrawListReqID' => $client_id,  # WithdrawList Request ID
            "Page" => $page,
            "PageSize" => $page_size,
            'StatusList' => $status             # example: ['1', '2'], 1-Pending, 2-In Progress, 4-Completed, 8-Cancelled
        ];

        $this->send($array);
    }
}

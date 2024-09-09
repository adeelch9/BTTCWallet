<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Web3p\EthereumWallet\Wallet as EthWallet;
use Web3p\EthereumUtil\Util;
use BitWasp\Bitcoin\Mnemonic\Bip39\Wordlist\EnglishWordList;
use Web3\Providers\HttpProvider;
use Web3\Formatters\BigNumberFormatter;
use Web3\Contract;
use Web3\Utils;
use Web3p\EthereumTx\Transaction as EthTransaction;
use phpseclib\Math\BigInteger;

class DPX extends Controller
{

    const ABI = '[{"inputs":[],"payable":false,"stateMutability":"nonpayable","type":"constructor"},{"anonymous":false,"inputs":[{"indexed":true,"internalType":"address","name":"previousAddress","type":"address"},{"indexed":true,"internalType":"address","name":"newAddress","type":"address"}],"name":"ChildChainChanged","type":"event"},{"anonymous":false,"inputs":[{"indexed":true,"internalType":"address","name":"token","type":"address"},{"indexed":true,"internalType":"address","name":"from","type":"address"},{"indexed":false,"internalType":"uint256","name":"amount","type":"uint256"},{"indexed":false,"internalType":"uint256","name":"input1","type":"uint256"},{"indexed":false,"internalType":"uint256","name":"output1","type":"uint256"}],"name":"Deposit","type":"event"},{"anonymous":false,"inputs":[{"indexed":true,"internalType":"address","name":"token","type":"address"},{"indexed":true,"internalType":"address","name":"from","type":"address"},{"indexed":true,"internalType":"address","name":"to","type":"address"},{"indexed":false,"internalType":"uint256","name":"amount","type":"uint256"},{"indexed":false,"internalType":"uint256","name":"input1","type":"uint256"},{"indexed":false,"internalType":"uint256","name":"input2","type":"uint256"},{"indexed":false,"internalType":"uint256","name":"output1","type":"uint256"},{"indexed":false,"internalType":"uint256","name":"output2","type":"uint256"}],"name":"LogFeeTransfer","type":"event"},{"anonymous":false,"inputs":[{"indexed":true,"internalType":"address","name":"token","type":"address"},{"indexed":true,"internalType":"address","name":"from","type":"address"},{"indexed":true,"internalType":"address","name":"to","type":"address"},{"indexed":false,"internalType":"uint256","name":"amount","type":"uint256"},{"indexed":false,"internalType":"uint256","name":"input1","type":"uint256"},{"indexed":false,"internalType":"uint256","name":"input2","type":"uint256"},{"indexed":false,"internalType":"uint256","name":"output1","type":"uint256"},{"indexed":false,"internalType":"uint256","name":"output2","type":"uint256"}],"name":"LogTransfer","type":"event"},{"anonymous":false,"inputs":[{"indexed":true,"internalType":"address","name":"previousOwner","type":"address"},{"indexed":true,"internalType":"address","name":"newOwner","type":"address"}],"name":"OwnershipTransferred","type":"event"},{"anonymous":false,"inputs":[{"indexed":true,"internalType":"address","name":"previousAddress","type":"address"},{"indexed":true,"internalType":"address","name":"newAddress","type":"address"}],"name":"ParentChanged","type":"event"},{"anonymous":false,"inputs":[{"indexed":true,"internalType":"address","name":"from","type":"address"},{"indexed":true,"internalType":"address","name":"to","type":"address"},{"indexed":false,"internalType":"uint256","name":"value","type":"uint256"}],"name":"Transfer","type":"event"},{"anonymous":false,"inputs":[{"indexed":true,"internalType":"address","name":"token","type":"address"},{"indexed":true,"internalType":"address","name":"from","type":"address"},{"indexed":false,"internalType":"uint256","name":"amount","type":"uint256"},{"indexed":false,"internalType":"uint256","name":"input1","type":"uint256"},{"indexed":false,"internalType":"uint256","name":"output1","type":"uint256"}],"name":"Withdraw","type":"event"},{"anonymous":false,"inputs":[{"indexed":true,"internalType":"address","name":"to","type":"address"}],"name":"WithdrawTo","type":"event"},{"anonymous":false,"inputs":[{"indexed":true,"internalType":"address","name":"from","type":"address"},{"indexed":true,"internalType":"address","name":"to","type":"address"},{"indexed":false,"internalType":"uint256","name":"amount","type":"uint256"}],"name":"WithdrawTo","type":"event"},{"constant":true,"inputs":[],"name":"CHAINID","outputs":[{"internalType":"uint256","name":"","type":"uint256"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":true,"inputs":[],"name":"EIP712_DOMAIN_HASH","outputs":[{"internalType":"bytes32","name":"","type":"bytes32"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":true,"inputs":[],"name":"EIP712_DOMAIN_SCHEMA_HASH","outputs":[{"internalType":"bytes32","name":"","type":"bytes32"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":true,"inputs":[],"name":"EIP712_TOKEN_TRANSFER_ORDER_SCHEMA_HASH","outputs":[{"internalType":"bytes32","name":"","type":"bytes32"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":false,"inputs":[{"internalType":"address","name":"newAddress","type":"address"}],"name":"changeChildChain","outputs":[],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":true,"inputs":[],"name":"childChain","outputs":[{"internalType":"address","name":"","type":"address"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":true,"inputs":[],"name":"currentSupply","outputs":[{"internalType":"uint256","name":"","type":"uint256"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":true,"inputs":[{"internalType":"bytes32","name":"","type":"bytes32"}],"name":"disabledHashes","outputs":[{"internalType":"bool","name":"","type":"bool"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":true,"inputs":[{"internalType":"bytes32","name":"hash","type":"bytes32"},{"internalType":"bytes","name":"sig","type":"bytes"}],"name":"ecrecovery","outputs":[{"internalType":"address","name":"result","type":"address"}],"payable":false,"stateMutability":"pure","type":"function"},{"constant":true,"inputs":[{"internalType":"address","name":"spender","type":"address"},{"internalType":"uint256","name":"tokenIdOrAmount","type":"uint256"},{"internalType":"bytes32","name":"data","type":"bytes32"},{"internalType":"uint256","name":"expiration","type":"uint256"}],"name":"getTokenTransferOrderHash","outputs":[{"internalType":"bytes32","name":"orderHash","type":"bytes32"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":true,"inputs":[],"name":"isOwner","outputs":[{"internalType":"bool","name":"","type":"bool"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":true,"inputs":[],"name":"networkId","outputs":[{"internalType":"bytes","name":"","type":"bytes"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":true,"inputs":[],"name":"owner","outputs":[{"internalType":"address","name":"","type":"address"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":true,"inputs":[],"name":"parent","outputs":[{"internalType":"address","name":"","type":"address"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":false,"inputs":[],"name":"renounceOwnership","outputs":[],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":true,"inputs":[],"name":"token","outputs":[{"internalType":"address","name":"","type":"address"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":false,"inputs":[{"internalType":"address","name":"newOwner","type":"address"}],"name":"transferOwnership","outputs":[],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":false,"inputs":[{"internalType":"bytes","name":"sig","type":"bytes"},{"internalType":"uint256","name":"amount","type":"uint256"},{"internalType":"bytes32","name":"data","type":"bytes32"},{"internalType":"uint256","name":"expiration","type":"uint256"},{"internalType":"address","name":"to","type":"address"}],"name":"transferWithSig","outputs":[{"internalType":"address","name":"from","type":"address"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":false,"inputs":[{"internalType":"address","name":"_childChain","type":"address"},{"internalType":"address","name":"_token","type":"address"}],"name":"initialize","outputs":[],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":false,"inputs":[{"internalType":"address","name":"","type":"address"}],"name":"setParent","outputs":[],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":false,"inputs":[{"internalType":"address","name":"user","type":"address"},{"internalType":"bytes","name":"depositData","type":"bytes"}],"name":"deposit","outputs":[],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":false,"inputs":[{"internalType":"address","name":"to","type":"address"},{"internalType":"uint256","name":"amount","type":"uint256"}],"name":"withdrawTo","outputs":[],"payable":true,"stateMutability":"payable","type":"function"},{"constant":false,"inputs":[{"internalType":"uint256","name":"amount","type":"uint256"}],"name":"withdraw","outputs":[],"payable":true,"stateMutability":"payable","type":"function"},{"constant":true,"inputs":[],"name":"name","outputs":[{"internalType":"string","name":"","type":"string"}],"payable":false,"stateMutability":"pure","type":"function"},{"constant":true,"inputs":[],"name":"symbol","outputs":[{"internalType":"string","name":"","type":"string"}],"payable":false,"stateMutability":"pure","type":"function"},{"constant":true,"inputs":[],"name":"decimals","outputs":[{"internalType":"uint8","name":"","type":"uint8"}],"payable":false,"stateMutability":"pure","type":"function"},{"constant":true,"inputs":[],"name":"totalSupply","outputs":[{"internalType":"uint256","name":"","type":"uint256"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":true,"inputs":[{"internalType":"address","name":"account","type":"address"}],"name":"balanceOf","outputs":[{"internalType":"uint256","name":"","type":"uint256"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":false,"inputs":[{"internalType":"address","name":"to","type":"address"},{"internalType":"uint256","name":"value","type":"uint256"}],"name":"transfer","outputs":[{"internalType":"bool","name":"","type":"bool"}],"payable":true,"stateMutability":"payable","type":"function"}]';
    const BYTE_CODE = '0x6080604052600436106101c25760003560e01c80638da5cb5b116100f7578063b789543c11610095578063e614d0d611610064578063e614d0d614610720578063ed9ef52414610735578063f2fde38b14610768578063fc0c546a1461079b576101c2565b8063b789543c14610624578063cc79f97b14610669578063cf2c52cb1461067e578063e306f7791461070b576101c2565b806395d89b41116100d157806395d89b41146105a4578063a9059cbb146105b9578063abceeba2146105e5578063acd06cb3146105fa576101c2565b80638da5cb5b146105515780638f32d59b146105665780639025e64c1461058f576101c2565b806342fc47fb1161016457806370a082311161013e57806370a082311461043a578063715018a61461046d578063771282f61461048257806377d32e9414610497576101c2565b806342fc47fb146103d5578063485cc955146103ea57806360f96a8f14610425576101c2565b806319d27d9c116101a057806319d27d9c146102ad578063205c2878146103615780632e1a7d4d1461038d578063313ce567146103aa576101c2565b806306fdde03146101c75780631499c5921461025157806318160ddd14610286575b600080fd5b3480156101d357600080fd5b506101dc6107b0565b6040805160208082528351818301528351919283929083019185019080838360005b838110156102165781810151838201526020016101fe565b50505050905090810190601f1680156102435780820380516001836020036101000a031916815260200191505b509250505060405180910390f35b34801561025d57600080fd5b506102846004803603602081101561027457600080fd5b50356001600160a01b03166107da565b005b34801561029257600080fd5b5061029b61081a565b60408051918252519081900360200190f35b3480156102b957600080fd5b50610345600480360360a08110156102d057600080fd5b8101906020810181356401000000008111156102eb57600080fd5b8201836020820111156102fd57600080fd5b8035906020019184600183028401116401000000008311171561031f57600080fd5b9193509150803590602081013590604081013590606001356001600160a01b031661082d565b604080516001600160a01b039092168252519081900360200190f35b6102846004803603604081101561037757600080fd5b506001600160a01b03813516906020013561086f565b610284600480360360208110156103a357600080fd5b50356109ca565b3480156103b657600080fd5b506103bf6109d7565b6040805160ff9092168252519081900360200190f35b3480156103e157600080fd5b506103456109dc565b3480156103f657600080fd5b506102846004803603604081101561040d57600080fd5b506001600160a01b03813581169160200135166109eb565b34801561043157600080fd5b50610345610a64565b34801561044657600080fd5b5061029b6004803603602081101561045d57600080fd5b50356001600160a01b0316610a73565b34801561047957600080fd5b50610284610a80565b34801561048e57600080fd5b5061029b610adb565b3480156104a357600080fd5b50610345600480360360408110156104ba57600080fd5b813591908101906040810160208201356401000000008111156104dc57600080fd5b8201836020820111156104ee57600080fd5b8035906020019184600183028401116401000000008311171561051057600080fd5b91908080601f016020809104026020016040519081016040528093929190818152602001838380828437600092019190915250929550610ae1945050505050565b34801561055d57600080fd5b50610345610c05565b34801561057257600080fd5b5061057b610c14565b604080519115158252519081900360200190f35b34801561059b57600080fd5b506101dc610c25565b3480156105b057600080fd5b506101dc610c44565b61057b600480360360408110156105cf57600080fd5b506001600160a01b038135169060200135610c61565b3480156105f157600080fd5b5061029b610c84565b34801561060657600080fd5b5061057b6004803603602081101561061d57600080fd5b5035610d0d565b34801561063057600080fd5b5061029b6004803603608081101561064757600080fd5b506001600160a01b038135169060208101359060408101359060600135610d22565b34801561067557600080fd5b5061029b610d41565b34801561068a57600080fd5b50610284600480360360408110156106a157600080fd5b6001600160a01b0382351691908101906040810160208201356401000000008111156106cc57600080fd5b8201836020820111156106de57600080fd5b8035906020019184600183028401116401000000008311171561070057600080fd5b509092509050610d46565b34801561071757600080fd5b5061029b610d7d565b34801561072c57600080fd5b5061029b610d83565b34801561074157600080fd5b506102846004803603602081101561075857600080fd5b50356001600160a01b0316610dcd565b34801561077457600080fd5b506102846004803603602081101561078b57600080fd5b50356001600160a01b0316610e7f565b3480156107a757600080fd5b50610345610e99565b60408051808201909152601081526f2134ba2a37b93932b73a102a37b5b2b760811b602082015290565b6040805162461bcd60e51b815260206004820152601060248201526f44697361626c6564206665617475726560801b604482015290519081900360640190fd5b6e01ed09bead87c0378d8e640000000090565b6040805162461bcd60e51b815260206004820152601060248201526f44697361626c6564206665617475726560801b6044820152905160009181900360640190fd5b33600061087b82610a73565b600654909150610891908463ffffffff610ea816565b60065582158015906108a257508234145b6108e9576040805162461bcd60e51b8152602060048201526013602482015272125b9cdd59999a58da595b9d08185b5bdd5b9d606a1b604482015290519081900360640190fd5b6002546001600160a01b0380841691167febff2602b3f468259e1e99f613fed6691f3a6526effe6ef3e768ba7ae7a36c4f858461092587610a73565b60408051938452602084019290925282820152519081900360600190a36040805184815290516000916001600160a01b038716917f67b714876402c93362735688659e2283b4a37fb21bab24bc759ca759ae851fd89181900360200190a36040805184815290516000916001600160a01b038516917fddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef9181900360200190a350505050565b6109d4338261086f565b50565b601290565b6003546001600160a01b031681565b60075460ff1615610a2d5760405162461bcd60e51b815260040180806020018281038252602381526020018061145e6023913960400191505060405180910390fd5b6007805460ff19166001179055600280546001600160a01b0383166001600160a01b0319909116179055610a6082610ebd565b5050565b6004546001600160a01b031681565b6001600160a01b03163190565b610a88610c14565b610a9157600080fd5b600080546040516001600160a01b03909116907f8be0079c531659141344cd1fd0a4f28419497f9722a3daafe3b4186f6b6457e0908390a3600080546001600160a01b0319169055565b60065481565b6000806000808451604114610afc5760009350505050610bff565b50505060208201516040830151604184015160ff16601b811015610b1e57601b015b8060ff16601b14158015610b3657508060ff16601c14155b15610b475760009350505050610bff565b6040805160008152602080820180845289905260ff8416828401526060820186905260808201859052915160019260a0808401939192601f1981019281900390910190855afa158015610b9e573d6000803e3d6000fd5b5050604051601f1901519450506001600160a01b038416610bfb576040805162461bcd60e51b815260206004820152601260248201527122b93937b91034b71032b1b932b1b7bb32b960711b604482015290519081900360640190fd5b5050505b92915050565b6000546001600160a01b031690565b6000546001600160a01b0316331490565b6040518060400160405280600381526020016231393960e81b81525081565b60408051808201909152600381526210951560ea1b602082015290565b6000813414610c7257506000610bff565b610c7d338484610f2b565b9392505050565b6040518060800160405280605b8152602001611528605b91396040516020018082805190602001908083835b60208310610ccf5780518252601f199092019160209182019101610cb0565b6001836020036101000a0380198251168184511680821785525050505050509050019150506040516020818303038152906040528051906020012081565b60056020526000908152604090205460ff1681565b6000610d38610d3386868686611178565b611231565b95945050505050565b60c781565b610d4e610c14565b610d5757600080fd5b600082826020811015610d6957600080fd5b50359050610d77848261123f565b50505050565b60015481565b6040518060800160405280605281526020016114a46052913960405160200180828051906020019080838360208310610ccf5780518252601f199092019160209182019101610cb0565b610dd5610c14565b610dde57600080fd5b6001600160a01b038116610e235760405162461bcd60e51b81526004018080602001828103825260328152602001806114f66032913960400191505060405180910390fd5b6003546040516001600160a01b038084169216907f1f9f3556dd336016cdf20adaead7d5c73665dba664b60e8c17e9a4eb91ce1d3990600090a3600380546001600160a01b0319166001600160a01b0392909216919091179055565b610e87610c14565b610e9057600080fd5b6109d481610ebd565b6002546001600160a01b031681565b600082821115610eb757600080fd5b50900390565b6001600160a01b038116610ed057600080fd5b600080546040516001600160a01b03808516939216917f8be0079c531659141344cd1fd0a4f28419497f9722a3daafe3b4186f6b6457e091a3600080546001600160a01b0319166001600160a01b0392909216919091179055565b604080516370a0823160e01b81526001600160a01b03851660048201529051600091829130916370a08231916024808301926020929190829003018186803b158015610f7657600080fd5b505afa158015610f8a573d6000803e3d6000fd5b505050506040513d6020811015610fa057600080fd5b5051604080516370a0823160e01b81526001600160a01b0387166004820152905191925060009130916370a08231916024808301926020929190829003018186803b158015610fee57600080fd5b505afa158015611002573d6000803e3d6000fd5b505050506040513d602081101561101857600080fd5b5051905061102786868661134e565b600254604080516370a0823160e01b81526001600160a01b03898116600483018190529251818a1694909116917fe6497e3ee548a3372136af2fcb0696db31fc6cf20260707645068bd3fe97f3c49189918891889130916370a0823191602480820192602092909190829003018186803b1580156110a457600080fd5b505afa1580156110b8573d6000803e3d6000fd5b505050506040513d60208110156110ce57600080fd5b5051604080516370a0823160e01b81526001600160a01b038f166004820152905130916370a08231916024808301926020929190829003018186803b15801561111657600080fd5b505afa15801561112a573d6000803e3d6000fd5b505050506040513d602081101561114057600080fd5b50516040805195865260208601949094528484019290925260608401526080830152519081900360a00190a450600195945050505050565b6000806040518060800160405280605b8152602001611528605b91396040516020018082805190602001908083835b602083106111c65780518252601f1990920191602091820191016111a7565b51815160209384036101000a60001901801990921691161790526040805192909401828103601f1901835280855282519282019290922082526001600160a01b039b909b169a81019a909a5250880196909652505050606084019190915260808301525060a0902090565b6000610bff82600154611429565b60008111801561125757506001600160a01b03821615155b6112925760405162461bcd60e51b81526004018080602001828103825260238152602001806114816023913960400191505060405180910390fd5b600061129d83610a73565b60405190915083906001600160a01b0382169084156108fc029085906000818181858888f193505050501580156112d8573d6000803e3d6000fd5b506006546112ec908463ffffffff61144b16565b6006556002546001600160a01b0380861691167f4e2ca0515ed1aef1395f66b5303bb5d6f1bf9d61a353fa53f73f8ac9973fa9f6858561132b89610a73565b60408051938452602084019290925282820152519081900360600190a350505050565b6001600160a01b0382163014156113a2576040805162461bcd60e51b8152602060048201526013602482015272063616e27742073656e6420746f204d5243323606c1b604482015290519081900360640190fd5b6040516001600160a01b0383169082156108fc029083906000818181858888f193505050501580156113d8573d6000803e3d6000fd5b50816001600160a01b0316836001600160a01b03167fddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef836040518082815260200191505060405180910390a3505050565b60405161190160f01b8152600281019190915260228101919091526042902090565b600082820183811015610c7d57600080fdfe54686520636f6e747261637420697320616c726561647920696e697469616c697a6564496e73756666696369656e7420616d6f756e74206f7220696e76616c69642075736572454950373132446f6d61696e28737472696e67206e616d652c737472696e672076657273696f6e2c75696e7432353620636861696e49642c6164647265737320766572696679696e67436f6e7472616374294368696c6420746f6b656e3a206e6577206368696c64206164647265737320697320746865207a65726f2061646472657373546f6b656e5472616e736665724f726465722861646472657373207370656e6465722c75696e7432353620746f6b656e49644f72416d6f756e742c6279746573333220646174612c75696e743235362065787069726174696f6e29a265627a7a7231582091f7e4735e67d86529beed9cb4c19fb24880cdcddb5e21c26589b5602ba0926d64736f6c63430005110032';

    const HTTP_PROVIDER = 'https://pre-rpc.bt.io';
    const MAINNET_HTTP_PROVIDER = 'https://bttc.trongrid.io';

    //BTTC Testnet Address
    const CONTRACT = [
        'contract_address' => '0x0000000000000000000000000000000000001010', // BTT ERC20
        'decimals' => 18,
    ];

    //BTTC Mainnet Address
    const CONTRACT_ETH = [
        'contract_address' => '0x0000000000000000000000000000000000001010', // BTT BRC20
        'decimals' => 18,
    ];
    public static function GenerateBTTCAddress()
    {
        try {
            $wordlist = new EnglishWordList();
            $wallet = new EthWallet($wordlist);
            $wallet = $wallet->generate(12);

            $addressData['address'] = $wallet->address;
            $addressData['privateKey'] = $wallet->privateKey;
            $addressData['hexAddress'] = $wallet->mnemonic;

        } catch (\Exception $e) {
            return null;
        }

        // {
        //     "addressData": {
        //         "address": "0x4700a41d8e5c3d79bb394289afafd5823d1ac2a6",
        //         "privateKey": "97644fd0c4ddc68d9b37e4996c5766cf1079d38b075e08ec95b355e6cc3b0a22",
        //         "hexAddress": "豪 務 懷 必 總 銳 粗 最 粉 旬 曾 融" //change hexAddress to mnemonic
        //     }
        // }

        return ['addressData' => $addressData];
    }

    public static function ValidateBTTCAddress(string $address, string $privateKey, string $hexAddress)
    {

        $wallet = Wallet::where('wallet', $address)->first();

        if ($wallet) {

            try {
                $util = new Util;
                $privateKeyToPublic = $util->privateKeyToPublicKey($privateKey);
                $publicKeyToAddress = $util->publicKeyToAddress($privateKeyToPublic);

            } catch (\Exception $e) {

                return ['addressData' => false];
            }


            if ($address === $publicKeyToAddress) {
                return ['addressData' => true];
            }

        }

        // {
        //     "status": "success",
        //     "result": true
        // }
        return ['addressData' => false];

    }


    public static function CreateWallet(string $wallet = null)
    {
        // Generate the wallet if not provided
        if (!$wallet) {
            $wallet = DPX::GenerateBTTCAddress();
        }
        // dd($wallet['addressData']->privateKey);
        // Access the 'addressData' object within the array
        if (isset($wallet['addressData']) && is_array($wallet['addressData'])) {

            $addressData = $wallet['addressData'];
            // Access the properties directly
            $address = $addressData['address'];
            $privateKey = $addressData['privateKey'];

            // Insert the wallet into the Wallet table
            Wallet::insert([
                'wallet' => $address,
                'secret' => Hash::make($privateKey),
                'hexAddress' => $addressData['hexAddress']
            ]);

            // Return the address and private key
            return [
                'wallet' => $address,
                'secret' => $privateKey,
                'hexAddress' => $addressData['hexAddress']
            ];
        } else {
            // Handle the case where addressData is not set or not an object
            throw new \Exception('Something went wrong. Please try again.');
        }
    }



    public static function Transfer(string $departure, string $destination, string $amount, string $secret, float $fee = null)
    {
        $wallet = Wallet::where('wallet', $departure)->first();

        if (!$wallet) {

            return API::Error('invalid-wallet', 'Wallet address does not exist on the Miniapp.');

        } else {

            try {
                $httpProvider = new HttpProvider(self::HTTP_PROVIDER, 100000);
                $contract = new Contract($httpProvider, self::ABI);
                $eth = $contract->eth;

                $amountInWei = Utils::toWei($amount, 'ether');

                $rawTransactionData = '0x' . $contract->at(self::CONTRACT_ETH['contract_address'])->getData('transfer', $destination, $amountInWei);


                // Get the transaction count
                $transactionCount = null;
                $eth->getTransactionCount($departure, function ($err, $transactionCountResult) use (&$transactionCount) {
                    if ($err) {
                        throw new \Exception('Error getting transaction count: ' . $err->getMessage());
                    } else {
                        $transactionCount = $transactionCountResult;
                    }
                });

                // Prepare the transaction parameters
                $transactionParams = [
                    'nonce' => "0x" . dechex($transactionCount->toString()),
                    'from' => $departure,
                    'to' => $destination,
                    'gas' => '0x' . dechex(20000000),
                    'value' => '0x0',
                    'data' => $rawTransactionData
                ];

                $gasLimit = 20000000;
                $eth->estimateGas($transactionParams, function ($err, $gas) use (&$gasLimit) {
                    if ($err) {
                        throw new \Exception('Error estimating gas price: ' . $err->getMessage());
                    } else {
                        $gasLimit = $gas;
                    }
                });



                // $factorToMultiplyGasEstimate = "50000000000";

                // $gasPriceMultiplied = hexdec(dechex($estimatedGas->toString())) * $factorToMultiplyGasEstimate;

                // $gasPriceMultiplied = BigNumberFormatter::format($gasPriceMultiplied);
                $gasPrice = 9000000000000000;

                // $calculatedGasPrice = $eth->estimateGas($transactionParams, function ($err, $gasPrice) use (&$calculatedGasPrice) {
                //     if ($err) {
                //         throw new \Exception('Error estimating gas price: ' . $err->getMessage());
                //     } else {
                //         $calculatedGasPrice = $gasPrice;
                //     }
                // });

                $transactionParams['gasPrice'] = "0x" . dechex($gasLimit->multiply(new BigInteger(500000000000))->toString());
                $transactionParams['chainId'] = 1029;

                // dd($transactionParams);
                $tx = new EthTransaction($transactionParams);

                $signedTx = '0x' . $tx->sign($secret);

                $txHash = null;
                $eth->sendRawTransaction($signedTx, function ($err, $txResult) use (&$txHash) {
                    if ($err) {
                        throw new \Exception('Error sending transaction: ' . $err->getMessage());
                    } else {
                        $txHash = $txResult;
                    }
                });

                $txReceipt = null;
                $secondsToWaitForReceipt = "300";

                for ($i = 0; $i <= $secondsToWaitForReceipt; $i++) {
                    $eth->getTransactionReceipt($txHash, function ($err, $txReceiptResult) use (&$txReceipt) {
                        if ($err) {
                            throw new \Exception("Error getting transaction receipt:" . $err->getMessage());
                        } else {
                            $txReceipt = $txReceiptResult;
                        }
                    });

                    if ($txReceipt) {
                        $responseData = [
                            "transaction" => $txHash,
                            "departure" => $departure,
                            "destination" => $destination,
                            "amount" => $amount,
                            "fee" => $fee ?? "0.2", // Set a default fee if not provided
                            "timestamp" => Carbon::now()->toDateTimeString(),
                        ];

                        Transaction::insert($responseData);
                        break;
                    }

                    sleep(1);
                }

            } catch (\Exception $e) {
                return API::Error('error', $e->getMessage());
            }

            return API::Respond($responseData ?? []);

        }

    }

    public static function Verify(string $wallet, string $secret, string $hexAddress)
    {

        $validatedAddress = DPX::ValidateBTTCAddress($wallet, $secret, $hexAddress);

        if ($validatedAddress['addressData'] === true) {

            return true;
        }

        return false;
    }


    public static function GetBalance(string $wallet)
    {

        $walletAddress = Wallet::where('wallet', $wallet)->first();

        if (!$walletAddress) {
            return API::Error('invalid-wallet', 'Address does not exist on MiniApp.');

        } else {
            try {
                // Initialize the HTTP provider and contract
                $httpProvider = new HttpProvider(self::HTTP_PROVIDER, 100000);
                $contract = new Contract($httpProvider, self::ABI);

                // Define a callback function for handling the balance result
                $balanceData = null;
                $contract->at(self::CONTRACT_ETH['contract_address'])->call('balanceOf', $wallet, ['from' => $wallet], function ($err, $results) use (&$balanceData) {
                    if ($err) {
                        throw new \Exception($err->getMessage());
                    }

                    if (!empty($results)) {
                        // Convert the result to a BigNumber instance
                        $bn = Utils::toBn($results[0]);
                        $balanceData = $bn->toString(); // Convert balance to string for easier handling
                    }
                });

                // Check if balanceData was successfully retrieved
                if ($balanceData !== null) {

                    return API::Respond($balanceData);

                } else {

                    return API::Error('invalid-wallet', 'Unable to retrieve balance. Wallet may be invalid.');

                }

            } catch (\Exception $e) {
                // Handle any exceptions that may occur during the process
                return API::Error('error', $e->getMessage());
            }
        }
    }


    public static function GetTransaction(string $transaction)
    {

        $transactionInfo = Transaction::where(['transaction' => $transaction])->first(['transaction', 'departure', 'destination', 'amount', 'fee', 'timestamp']);

        return $transactionInfo ? API::Respond($transactionInfo) : API::Error('invalid-transaction', 'Transaction is invalid');
    }

    public static function GetTransactions(int $offset = 0, string|null $departure = null, string|null $destination = null)
    {

        if ($departure && $destination) {

            if ($departure === $destination) {

                $transactions = Transaction::where(['departure' => $departure])
                    ->orWhere(['destination' => $destination])
                    ->orderby('id', 'DESC')
                    ->limit(env('TRANSACTIONS_PER_FETCH', 250))
                    ->offset($offset)
                    ->get(['transaction', 'departure', 'destination', 'amount', 'fee', 'timestamp']);
            } else {

                $transactions = Transaction::where(['departure' => $departure, 'destination' => $destination])
                    ->orderby('id', 'DESC')
                    ->limit(env('TRANSACTIONS_PER_FETCH', 250))
                    ->offset($offset)
                    ->get(['transaction', 'departure', 'destination', 'amount', 'fee', 'timestamp']);
            }
        } else if ($departure) {

            $transactions = Transaction::where(['departure' => $departure])
                ->orderby('id', 'DESC')
                ->limit(env('TRANSACTIONS_PER_FETCH', 250))
                ->offset($offset)
                ->get(['transaction', 'departure', 'destination', 'amount', 'fee', 'timestamp']);
        } else if ($destination) {

            $transactions = Transaction::where(['destination' => $destination])
                ->orderby('id', 'DESC')
                ->limit(env('TRANSACTIONS_PER_FETCH', 250))
                ->offset($offset)
                ->get(['transaction', 'departure', 'destination', 'amount', 'fee', 'timestamp']);
        } else {

            $transactions = Transaction::orderby('id', 'DESC')
                ->limit(env('TRANSACTIONS_PER_FETCH', 250))
                ->offset($offset)
                ->get(['transaction', 'departure', 'destination', 'amount', 'fee', 'timestamp']);
        }

        $transactions = json_decode(json_encode($transactions), true);

        return API::Respond($transactions, false);
    }

}

<?php
namespace Src;

require_once '../vendor/autoload.php';

class Transaction
{
    private $db;
    private $requestMethod;
    private $defaultWallet;
    private $defaultPage;
    private $defaultOffset;

    public function __construct($db, $requestMethod)
    {
        $this->db = $db;
        $this->requestMethod = $requestMethod;
        $this->defaultWallet = "0xAdBD2bf598ec0C343ffBc57DD4e0417555CE7b02";
        $this->defaultPage = 1;
        $this->defaultOffset = 50;
    }

    public function processRequest()
    {
        switch ($this->requestMethod) {
            case 'GET':
                $response = $this->getTransactions();
                break;
            case 'POST':
                break;
            case 'PUT':
                break;
            case 'DELETE':
                break;
            default:
                break;
        }
        header($response['status_code_header']);
        if ($response['body']) {
            echo $response['body'];
        }
    }

    private function didMalformed()
    {
        $response['status_code_header'] = 'HTTP/1.1 404 Not Found';
        $response['body'] = json_encode([
            'error' => 'DID is Malformed',
        ]);
        return $response;
    }

    private function getEmail()
    {
        if (function_exists('getallheaders')) {
            

            $did_token = \MagicAdmin\Util\Http::parse_authorization_header_value(getallheaders()['Authorization']);
            $headers =  getallheaders();

            // DIDT is missing from the original HTTP request header. 404: DID Missing
            if ($did_token == null) {
                return $this->didMissing();
            }

            $magic = new \MagicAdmin\Magic($_ENV['MAGIC_SECRET_KEY']);

            try {
                $magic->token->validate($did_token);
                $issuer = $magic->token->get_issuer($did_token);
                $user_meta = $magic->user->get_metadata_by_issuer($issuer);
                return $user_meta->data->email;
            } catch (\MagicAdmin\Exception\DIDTokenException$e) {
                // DIDT is malformed.
                return $this->didMalformed();
            }
        }
    }


    private function fetchUserTransactions($wallet, $page, $account_id)
    {
        $this->logToFile('Call fetchUserTransactions \n');
        $numberOfTx = 50;
        while ($numberOfTx >= $this->defaultOffset)
        {
            $url = 'https://api.etherscan.io/api?module=account&action=txlist&address=' . $wallet . '&startblock=0&endblock=99999999&page=' . $page . '&offset=50&sort=asc&apikey=' . $_ENV['ETHER_KEY'];
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response_json = curl_exec($ch);
            curl_close($ch);
            $api_response = json_decode($response_json, true);
            $numberOfTx = count($api_response['result']);
            if ($numberOfTx > 0) {
                $this->storeUserTransactions($api_response['result'], $account_id);
            }
            if ($numberOfTx >= $this->defaultOffset)
            {
                $page = $page + 1;
            }
        }
        $this->updateUserPage($page, $account_id);
    }

    private function findUser($email)
    {
        $query = "
            SELECT
                account_id, email, page
            FROM
                user
            WHERE email = :email;
        ";

        try {
            $statement = $this->db->prepare($query);
            $statement->execute(array('email' => $email));
            $result = $statement->fetch(\PDO::FETCH_ASSOC);
            return $result;
        } catch (\PDOException$e) {
            exit($e->getMessage());
        }
    }

    private function updateUserPage($page, $account_id)
    {
        $query = "
            UPDATE user
            SET
                page = :page
            WHERE account_id = :account_id;
        ";

        $statement = $this->db->prepare($query);
        $statement->execute(array(
            'account_id' => $account_id,
            'page' => (int)$page,
        ));
    }

    private function createNewUser($email, $account_id, $page, $wallet)
    {
        $query = "
            INSERT INTO user
                (account_id, email, page, wallet)
            VALUES
                (:account_id, :email, :page, :wallet);
            ";
        $statement = $this->db->prepare($query);
        $statement->execute(array(
            'account_id' => $account_id,
            'email' => $email,
            'page' => $page,
            'wallet' => $wallet,
        ));
    }

    private function storeUserTransactions($userTransactions, $account_id)
    {
        foreach($userTransactions as $tx) {
            $tx_id = mt_rand(100000,999999);
            $txhash = $tx['hash'];
            $methodId = $tx['methodId'];
            $timeStamp = $tx['timeStamp'];
            $fromW = $tx['from'];
            $toW = $tx['to'];
            $query = "
            INSERT IGNORE INTO user_transactions
                (tx_id, txhash, methodId, timeStamp, fromW, toW, account_id)
            VALUES
                (:tx_id, :txhash, :methodId, :timeStamp, :fromW, :toW, :account_id);
            ";
            $statement = $this->db->prepare($query);
            $statement->execute(array(
                'tx_id' => $tx_id,
                'txhash' => $txhash,
                'methodId' => $methodId,
                'timeStamp' => $timeStamp,
                'fromW' => $fromW,
                'toW' => $toW,
                'account_id' => $account_id,
            ));
            
        }
    }

    private function getUserTransactions($account_id)
    {
        $query = "
        SELECT
            txhash, methodId, timeStamp, fromW, toW, account_id
        FROM
            user_transactions;
        WHERE account_id = :account_id;
        ";

        $statement = $this->db->prepare($query);
        $statement->execute(array('account_id' => $account_id));
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }


    private function getTransactions()
    {    
        $email = $this->getEmail();
        if (is_string($email)) {
            $response['status_code_header'] = 'HTTP/1.1 201 Created';
            $response['body'] = json_encode(array('email' => $email));
            $user = $this->findUser($email);
            if (!$user) {
                // create new account
                $account_id = mt_rand(100000,999999);
                $this->createNewUser($email, $account_id, $this->defaultPage, $this->defaultWallet);
                $this->fetchUserTransactions($this->defaultWallet, $this->defaultPage, $account_id);
            } else {
                $this->fetchUserTransactions($this->defaultWallet, $user['page'], $user['account_id']);
            }
            // return user tx
            $user_transactions = $this->getUserTransactions($user['account_id']);
            $response['body'] = json_encode(array(
                'user_transactions' => $user_transactions,
                'email' => $email,
            ));
            return $response;
        } else {
            return $this->didMissing();
        }
    }

    private function didMissing()
    {
        $response['status_code_header'] = 'HTTP/1.1 404 Not Found';
        $response['body'] = json_encode([
            'error' => 'DID is Missing on Header.',
        ]);
        return $response;
    }

    private function logToFile($text)
    {
        $myFile = "log.txt";
        $fh = fopen($myFile, 'a') or die("can't open file");
        fwrite($fh, $text);
        fclose($fh);
    }
}

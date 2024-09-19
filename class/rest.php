<?php
class Rest {
    private $host = 'localhost';
    private $user = 'root';
    private $password = '';
    private $database = "marvico_foods";
    private $dbConnect = false;

    public function __construct() {
        if (!$this->dbConnect) {
            $this->dbConnect = new mysqli($this->host, $this->user, $this->password, $this->database);
            if ($this->dbConnect->connect_error) {
                die("Error failed to connect to MySQL: " . $this->dbConnect->connect_error);
            }
        }
    }

    private function execute_query($query, $params = []) {
        $stmt = $this->dbConnect->prepare($query);
        if ($stmt === false) {
            die("Error preparing query: " . $this->dbConnect->error);
        }
        if ($params) {
            $types = str_repeat('s', count($params)); // Assuming all parameters are strings
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result === false) {
            die("Error executing query: " . $stmt->error);
        }
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function response_details($response_details) {
        $query = "INSERT INTO test_tb (value_1) VALUES (?)";
        $stmt = $this->dbConnect->prepare($query);
        $stmt->bind_param('s', $response_details);
        if ($stmt->execute()) {
            // Assuming $customer_id and $transaction_id are defined elsewhere or passed in
            $this->payment_level_tracker($customer_id, $transaction_id, '1', 'Payment Received Successfully');
            $response = $this->wallet_funding($customer_id, $amount_paid, $transaction_id);
            $status = $response['responsecode'] == '0' ? '5' : '5';
            $this->payment_level_tracker($customer_id, $transaction_id, $status, $response['message']);
        } else {
            $this->payment_level_tracker($customer_id, $transaction_id, '1', 'Payment Not Received Successfully');
        }
    }

    public function get_callback_details($customer_id) {
        $query = "SELECT header, url, ckey FROM client_callback_configs WHERE customer_id = ?";
        $result = $this->execute_query($query, [$customer_id]);
        return empty($result) ? ["status_code" => '1', "result" => []] : ["status_code" => '0', "result" => $result];
    }

    public function get_key() {
        $query = "SELECT auth_key FROM authorizations";
        $result = $this->execute_query($query);
        
        // Check if result is empty and return an appropriate response
        if (empty($result)) {
            return ["status_code" => '1', "result" => [], "message" => "Authorization table or key not found"];
        }
        
        return ["status_code" => '0', "result" => $result];
    }

    public function client_header($header_values) {
        $v1 = $header_values['Client-Id'] ?? '';
        $v2 = $header_values['X-Auth-Signature'] ?? '';
        return "$v1|$v2";
    }

    function get_marvico_food_reference($account_no) {
        $response = array("status_code" => '1', "result" => array());
        $result = $this->execute_query("SELECT user_id FROM marvico_food_txn WHERE account_no = ?", array($account_no));
        
        if (!empty($result)) {
            $response = array("status_code" => '0', "result" => $result[0]);
        }
        
        return $response;    
    }

    function get_marvico_food_account($account_no) {
        $response = array("status_code" => '1', "result" => array());
        $result = $this->execute_query("SELECT user_id, register_name, phonenumber, email, product_items, subtotal, shipping, tax, total_amount, account_no, completed_status FROM marvico_food_txn WHERE account_no = ?", array($account_no));
        
        if (!empty($result)) {
            $response = array("status_code" => '0', "result" => $result[0]);
        }
        
        return $response;
    }
    // function marvico_food_settlement_details($data) {
    //     $key = ''; // This is empty in your current setup
    //     $account_no = $data['account_no'];
    
    //     // Get due_reference and completed_status
    //     $account_info = $this->get_marvico_food_account($account_no)['result'];
    //     $user_id = $this->get_marvico_food_reference($account_no)['result']['user_id'] ?? '';
    //     $completed_status = $account_info['completed_status'] ?? '0';
    
    //     // Prepare data
    //     $transaction_id = $data['transaction_id'];
    //     $tran_remarks = $data['tran_remarks'];
    //     $source_account_name = $data['source_account_name'] ?: 'Anonymous';
    //     $source_bank_name = $data['source_bank_name'];
    //     $source_account_number = $data['source_account_number'];
    //     $amount = $data['amount'];
    //     $payment_dt = date('Y-m-d H:i:s');
    //     $hash_value = hash('sha512', "$transaction_id|$account_no|$tran_remarks|$source_account_number|$source_account_name|$source_bank_name|$key|$amount");
    
    //     // Check hash validation
    //     $check_hash_status = '0';
    //     $trans_id = date('YmdHis'); // Unique transaction ID
    //     $dt = date('Y-m-d H:i:s');
    
    //     if ($account_no !== "9633888405" && $completed_status == '1') {
    //         // Update existing transaction log
    //         $query = "UPDATE marvico_food_txn 
    //                   SET tran_reference = ?, trans_id = ?, sender_name = ?, payment_channel = 'VIRTUAL_ACCOUNT', payment_status = 'successful', payment_dt = ?, completed_status = ?, paid_amount = ?, transaction_dt = ?, bank_name = ? 
    //                   WHERE user_id = ? AND completed_status = '1'";
    
    //         $params = array($transaction_id, $trans_id, $source_account_name, $payment_dt, $check_hash_status, $amount, $dt, $source_bank_name, $user_id);
    
    //         // Bind parameters for MySQLi
    //         $stmt = $this->dbConnect->prepare($query);
    //         $stmt->bind_param('ssssssss', ...$params);
    
    //         if ($stmt->execute()) {
    //             // Update the cart status after successful transaction
    //             $this->update_cart_status($account_info['user_id']);
    //             echo "Transaction Successful";
    //         } else {
    //             echo "Transaction Unsuccessful";
    //         }
    //     } elseif ($account_no !== "9633888405" && $completed_status == '0') {
    //         // Insert new transaction log
    //         $query = "INSERT INTO marvico_food_txn (user_id, register_name, email, phonenumber, total_amount, product_items, subtotal, shipping, tax, account_no, completed_status, tran_reference, trans_id, sender_name, payment_channel, payment_status, payment_dt, paid_amount, time_in, bank_name)
    //                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'VIRTUAL_ACCOUNT', 'successful', ?, ?, ?, ?)";
    
    //  $params = array(
    //     $account_info['user_id'], $account_info['register_name'], $account_info['email'], $account_info['phonenumber'], $account_info['total_amount'],
    //     json_encode($account_info['product_items']), $account_info['subtotal'], $account_info['shipping'], $account_info['tax'], $account_no, $completed_status,
    //     $transaction_id, $trans_id, $source_account_name, $payment_dt, $check_hash_status, $amount, $dt, $source_bank_name
    //  );
    //         // Bind parameters for MySQLi
    //         $stmt = $this->dbConnect->prepare($query);
    //         $stmt->bind_param('ssssssssssssssssssss', ...$params);
    
    //         if ($stmt->execute()) {
    //             echo "Transaction Successful";
    //         } else {
    //             echo "Transaction Unsuccessful";
    //         }
    //     }
    // }

    function marvico_food_settlement_details($data) {
        $key = ''; // This is empty in your current setup
        $account_no = $data['account_no'];
    
        // Get due_reference and completed_status
        $account_info = $this->get_marvico_food_account($account_no)['result'];
        $user_id = $this->get_marvico_food_reference($account_no)['result']['user_id'] ?? '';
        $completed_status = $account_info['completed_status'] ?? '0';
    
        // Prepare data
        $transaction_id = $data['transaction_id'];
        $tran_remarks = $data['tran_remarks'];
        $source_account_name = $data['source_account_name'] ?: 'Anonymous';
        $source_bank_name = $data['source_bank_name'];
        $source_account_number = $data['source_account_number'];
        $amount = $data['amount'];
        $payment_dt = date('Y-m-d H:i:s');
        $hash_value = hash('sha512', "$transaction_id|$account_no|$tran_remarks|$source_account_number|$source_account_name|$source_bank_name|$key|$amount");
    
        // Check hash validation
        $check_hash_status = '0';
        $trans_id = date('YmdHis'); // Unique transaction ID
        $dt = date('Y-m-d H:i:s');
    
        if ($account_no !== "9997038584" && $completed_status == '1') {
            // Update existing transaction log
            $query = "UPDATE marvico_food_txn 
                      SET tran_reference = ?, trans_id = ?, sender_name = ?, payment_channel = 'VIRTUAL_ACCOUNT', payment_status = 'successful', payment_dt = ?, completed_status = ?, paid_amount = ?, transaction_dt = ?, bank_name = ? 
                      WHERE user_id = ? AND completed_status = '1'";
    
            $params = array($transaction_id, $trans_id, $source_account_name, $payment_dt, $check_hash_status, $amount, $dt, $source_bank_name, $user_id);
    
            // Prepare and bind parameters for MySQLi
            $stmt = $this->dbConnect->prepare($query);
            if ($stmt === false) {
                die("Prepare failed: " . $this->dbConnect->error);
            }
    
            // Bind parameters (all are strings in this case)
            $stmt->bind_param('sssssssss', 
                $transaction_id, 
                $trans_id, 
                $source_account_name, 
                $payment_dt, 
                $check_hash_status, 
                $amount, 
                $dt, 
                $source_bank_name, 
                $user_id
            );
    
            // Execute the statement
            if ($stmt->execute()) {
                echo "Transaction Successful";
            } else {
                echo "Transaction Unsuccessful: " . $this->dbConnect->error;
            }
    
            $stmt->close();
        }elseif ($account_no !== "9997038584" && $completed_status == '0') {
            $payment_channel = 'VIRTUAL_ACCOUNT';
            $payment_status = 'successful';
        
            // Prepare the query with proper placeholders
            $query = "INSERT INTO marvico_food_txn (
                user_id, register_name, email, phonenumber, total_amount, product_items, subtotal, shipping, tax, account_no, completed_status, tran_reference, trans_id, sender_name, payment_channel, payment_status, payment_dt, paid_amount, time_in, bank_name
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
            // Prepare the statement
            $stmt = $this->dbConnect->prepare($query);
            if ($stmt === false) {
                die("Prepare failed: " . $this->dbConnect->error);
            }
        
            // Define the parameter types
            $types = 'ssssddddssssssssds'; // 4 strings, 4 doubles, 8 more strings, 1 double
        
            // Define parameters array
            $params = array(
                $account_info['user_id'], 
                $account_info['register_name'], 
                $account_info['email'], 
                $account_info['phonenumber'], 
                (float)$account_info['total_amount'], // Convert to double
                json_encode($account_info['product_items']), 
                (float)$account_info['subtotal'], // Convert to double
                (float)$account_info['shipping'], // Convert to double
                (float)$account_info['tax'], // Convert to double
                $account_no, 
                $completed_status,
                $transaction_id, 
                $trans_id, 
                $source_account_name,
                $payment_channel, 
                $payment_status, 
                $payment_dt, 
                (float)$amount, // Convert to double
                $dt, 
                $source_bank_name
            );
        
            // Bind parameters
            $stmt->bind_param($types, 
                ...$params
            );
        
            // Execute the statement
            if ($stmt->execute()) {
                echo "Transaction Successful";
            } else {
                echo "Transaction Unsuccessful: " . $this->dbConnect->error;
            }
        
            $stmt->close();
        }
        
        
        
    }
    
    
    
    
    
    
    
    private function update_cart_status($user_id) {
        $update_cart_query = "UPDATE cart SET status = 0 WHERE user_id = ?";
        $stmt = $this->dbConnect->prepare($update_cart_query);
        $stmt->bind_param('s', $user_id);
        
        if ($stmt->execute()) {
            // Cart status updated successfully
        } else {
            // Handle error
        }
    }
}
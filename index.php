<?php
 
class ControllerExtensionPaymentIyzicoForm extends Controller {
	
    public function index() {
         
    }
								  
	private function refundPayment($locale, $paymentId, $remoteIpAddr, $apiKey, $secretKey, $rand, $order_id, $amount, $currency_code,$iyzico_paymentTransactionId)
    {
        $responseObject     = $this->refundObject($locale, $paymentId, $remoteIpAddr, $order_id, $amount, $currency_code, $iyzico_paymentTransactionId);
		
        $pkiString          = $this->pkiStringGenerate($responseObject);
		 
        $authorization      = $this->authorization($pkiString, $apiKey, $secretKey, $rand);

        $responseObjectLast = json_encode($responseObject, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $refundResponse     = $this->paymentRefund($authorization,$responseObjectLast);
		
		return $refundResponse;
    }
 
	
	public function refundObject($locale, $paymentId, $ip, $order_id, $amount, $currency_code, $iyzico_paymentTransactionId)
    {

        $responseObject                                = new stdClass();
		
		$responseObject->locale                        = ''.$locale.'';
		$responseObject->conversationId                = ''.$order_id.'';
		$responseObject->paymentTransactionId          = ''.$iyzico_paymentTransactionId.'';
		$responseObject->price                         = ''.$amount.'';
		$responseObject->ip                            = ''.$ip.'';
		$responseObject->currency                      = ''.$currency_code.'';
		$responseObject->reason                        = 'other';
		$responseObject->description                   = 'customer requested for default sample';
	
        return $responseObject;
    }
 
 
	public function paymentRefund($authorization, $json)
    {
		
		$url 		= $this->config->get('payment_iyzico_api_url');
        $url 		= $url.'/payment/iyzipos/refund';


        return $this->curlPost($json, $authorization, $url);
		
    }
	
	
	
	private function cancelPayment($locale, $paymentId, $remoteIpAddr, $apiKey, $secretKey, $rand, $order_id)
    {
		
		
        $responseObject = $this->cancelObject($locale, $paymentId, $remoteIpAddr, $order_id);
		
        $pkiString      = $this->pkiStringGenerate($responseObject);

        $authorization  = $this->authorization($pkiString, $apiKey, $secretKey, $rand);

        $responseObjectLast = json_encode($responseObject, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		
		
        $cancelResponse = $this->paymentCancel($authorization,$responseObjectLast);
		

		
		return $cancelResponse;
    }
 
	
	public function cancelObject($locale, $paymentId, $ip, $order_id)
    {

        $responseObject                    = new stdClass();
	
		$responseObject->locale            = ''.$locale.'';
		$responseObject->conversationId    = ''.$order_id.'';
		$responseObject->paymentId         = ''.$paymentId.'';
		$responseObject->ip                = ''.$ip.'';
		
        return $responseObject;
    }
 
 
 	public function paymentCancel($authorization, $json)
    {
		
		$url 		= $this->config->get('payment_iyzico_api_url');
        $url 		= $url.'/payment/iyzipos/cancel';

        return $this->curlPost($json, $authorization, $url);
    }
	
 
 
 
     public function pkiStringGenerate($objectData)
    {
        $pki_value = '[';

        foreach ($objectData as $key => $data) {
            if (is_object($data)) {
                $name = var_export($key, true);
                $name = str_replace("'", '', $name);
                $pki_value .= $name.'=[';
                $end_key = count(get_object_vars($data));
                $count = 0;

                foreach ($data as $key => $value) {
                    ++$count;
                    $name = var_export($key, true);
                    $name = str_replace("'", '', $name);
                    $pki_value .= $name.'='.''.$value;
                    if ($end_key != $count) {
                        $pki_value .= ',';
                    }
                }

                $pki_value .= ']';
            } elseif (is_array($data)) {
                $name = var_export($key, true);
                $name = str_replace("'", '', $name);
                $pki_value .= $name.'=[';
                $end_key = count($data);
                $count = 0;

                foreach ($data as $key => $result) {
                    ++$count;
                    $pki_value .= '[';

                    foreach ($result as $key => $item) {
                        $name = var_export($key, true);
                        $name = str_replace("'", '', $name);
                        $pki_value .= $name.'='.''.$item;
                        if (end($result) != $item) {
                            $pki_value .= ',';
                        }
                        if (end($result) == $item) {
                            if ($end_key != $count) {
                                $pki_value .= '], ';
                            } else {
                                $pki_value .= ']';
                            }
                        }
                    }
                }
                if (end($data) == $result) {
                    $pki_value .= ']';
                }
            } else {
                $name = var_export($key, true);
                $name = str_replace("'", '', $name);
                $pki_value .= $name.'='.''.$data.'';
            }
            if (end($objectData) != $data) {
                $pki_value .= ',';
            }
        }
        $pki_value .= ']';

        return $pki_value;
    }

    public function authorization($pkiString, $apiKey, $secretKey, $rand)
    {
        $hash_value         = $apiKey.$rand.$secretKey.$pkiString;
        $hash               = base64_encode(sha1($hash_value, true));
        $authorizationText  = 'IYZWS '.$apiKey.':'.$hash;

        $authorization      = array(
            'authorization' => $authorizationText,
            'randValue'     => $rand,
        );

        return $authorization;

		
    }
 
 
  public function apiConnection($authorization_data,$api_connection_object) {

        $url 		= $this->config->get('payment_iyzico_api_url');
        $url 		= $url.'/payment/bin/check';

        $api_connection_object = json_encode($api_connection_object);

        return $this->curlPost($api_connection_object,$authorization_data,$url);

    }



	public function testApi () {
		
		$api_con_object                   = new stdClass();
		$api_con_object->locale           = $this->language->get('code');
		$api_con_object->conversationId   = rand(100000,99999999);
		$api_con_object->binNumber        = '454671';

		$apiKey                           = $this->config->get('payment_iyzico_api_key');
		$secretKey                        = $this->config->get('payment_iyzico_secret_key');

		$pkiString                        = $this->pkiStringGenerate($api_con_object); 
		$rand                             = rand(100000,99999999);
		$authorization                    = $this->authorization($pkiString, $apiKey, $secretKey, $rand);
		$test_api_con                     = $this->apiConnection($authorization,$api_con_object);

		return $test_api_con;
		
	}


 
    public function curlPost($json, $authorization, $endpoint)
    {
		
		
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $endpoint);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 150);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                "Authorization:".$authorization['authorization']."",
                "x-iyzi-rnd:".$authorization['randValue']."",
				"x-iyzi-client-version:iyzipay-php-2.0.51",
                "Content-Type: application/json"
        ));
		
        $result = json_decode(curl_exec($curl));
		
        curl_close($curl);

        return $result;
    }
	


	
	private function priceParser($price) {

        if (strpos($price, ".") === false) {
            return $price . ".0";
        }
        $subStrIndex = 0;
        $priceReversed = strrev($price);
        for ($i = 0; $i < strlen($priceReversed); $i++) {
            if (strcmp($priceReversed[$i], "0") == 0) {
                $subStrIndex = $i + 1;
            } else if (strcmp($priceReversed[$i], ".") == 0) {
                $priceReversed = "0" . $priceReversed;
                break;
            } else {
                break;
            }
        }

        return strrev(substr($priceReversed, $subStrIndex));
    }

    public function order() {


        $this->load->model('extension/payment/iyzico');
        $this->language->load('extension/payment/iyzico');
		
        $language_id        = (int) $this->config->get('config_language_id');
        $data               = array();
        $order_id           = (int) $this->request->get['order_id'];
        $data['user_token'] = $this->request->get['user_token'];

        $data['order_id']                       = $order_id;
        $data['text_payment_cancel']            = 'Ödeme İptal';
        $data['text_order_cancel']              = 'İşlemi İptal Et';
        $data['text_items']                     = 'Ürünler';
        $data['text_item_name']                 = 'Ürün Adı';
        $data['text_paid_price']                = 'Tahsil Edilen Tutar';
        $data['text_total_refunded_amount']     = 'İade Edilen Tutar';
        $data['text_action']                    = 'Aksiyon';
        $data['text_refund']                    = 'İade';
        $data['text_transactions']              = 'Ürünler';
        $data['text_date_added']                = 'Eklenme Tarihi';
        $data['text_type']                      = 'Tip';
        $data['text_status']                    = 'Durum';
        $data['text_note']                      = 'Not';
        $data['text_are_you_sure']              = 'Emin misiniz?';
        $data['text_please_enter_amount']       = 'Lütfen Tutar Giriniz.';
        
        $this->load->model('sale/order');
        $order_details = $this->model_sale_order->getOrder($order_id);
 
        $refunded_transactions_query_string = "
	SELECT rf.*, pd.name FROM " . DB_PREFIX . "iyzico_order_refunds rf  
	LEFT JOIN " . DB_PREFIX . "product_description pd  ON pd.product_id = rf.iyzico_item_id  
	WHERE rf.iyzico_order_id = '{$order_id}' AND pd.language_id = {$language_id} ORDER BY iyzico_order_refunds_id ASC";
	    
        $refunded_transactions_query = $this->db->query($refunded_transactions_query_string);
		
		
	$data['refunded_item_iyzico_response_all']  = false;
	$data['refunded_item_iyzico_response']      = false;
	$refunded_item_iyzico_response_note         = '';


        foreach ($refunded_transactions_query->rows as $refunded_item) {
			
			 
		$refunded_item['response_note']                         =  '';
		$iyzico_paidPrice                                       =  $refunded_item['iyzico_paidPrice'];

		if($refunded_item['iyzico_paidPrice'] == $refunded_item['iyzico_total_refunded']){
			$iyzico_total_refunded                         =  $refunded_item['iyzico_total_refunded'];

		}

		if($refunded_item['iyzico_item_paid_price'] == $refunded_item['iyzico_total_refunded']){
			$iyzico_total_refunded                        +=  $refunded_item['iyzico_total_refunded'];
		}




		if(isset($refunded_item['iyzico_response'])){	


			$iyzico_response                                    =  json_decode($refunded_item['iyzico_response'], true);
			$refunded_item['response_note']                     =  'İade Edildi';
			$refunded_item_iyzico_response_note                 =  $iyzico_response['response_note'];

		}
			
            $refunded_item['paid_price_converted']      = $this->currency->format($refunded_item['iyzico_paidPrice'], $order_details['currency_code'], false);
            $refunded_item['total_refunded_converted']  = $this->currency->format($refunded_item['iyzico_total_refunded'], $order_details['currency_code'], false);
            $refunded_item['full_refunded']             = ($refunded_item['iyzico_paidPrice'] == $refunded_item['iyzico_total_refunded']) ? '0' : '1';
            $refunded_item['refunded_item_status']      = ($refunded_item['iyzico_item_paid_price'] == $refunded_item['iyzico_total_refunded']) ? '0' : '1';
            $refunded_item['remaining_refund_amount']   =  $refunded_item['iyzico_total_refunded'] ;
           
           
            $data['iyzico_transactions_refunds_data'][] = $refunded_item;
        }
		
		
		if( $iyzico_total_refunded == $iyzico_paidPrice ){
			$data['refunded_item_iyzico_response_all']  = true;
			$data['refunded_item_iyzico_response']      = false;
			$data['refunded_item_iyzico_response_note'] = $refunded_item_iyzico_response_note;
		}
		 
		if( $iyzico_total_refunded != $iyzico_paidPrice && $iyzico_total_refunded > 0 ){
			$data['refunded_item_iyzico_response_all']  = true;
			$data['refunded_item_iyzico_response']      = true;
			$data['refunded_item_iyzico_response_note'] = 'Kısmi iade yapıldıktan sonra iptal yapılamaz';
		}
		 
		// API login
			$this->load->model('user/api');

	
		return  $this->response->setOutput($this->load->view('extension/payment/iyzico_form_order', $data)) ;
    }

 
    public function cancel() {
		
		
		//print_r($this->testApi()); 
		
        $order_id = $this->request->post['order_id'];
        $data     = array();
		
		
        try {

            if (!$order_id) {
               
		$data['message']   = 'Geçersiz Sipariş';
		$data['success']   = false;

            }else{

		$this->load->model('extension/payment/iyzico');

		$query      = $this->db->query("
		SELECT * FROM `" . DB_PREFIX . "iyzico_order` WHERE `order_id` = '{$order_id}' AND `status` = 'SUCCESS' 
		ORDER BY `iyzico_order_id` DESC")->row;

		$language   = $this->config->get('payment_iyzico_language');


		if(empty($language) or $language == 'null')
		{
			$locale  		= $this->language->get('code');
		}elseif ($language == 'TR' or $language == 'tr') {
			$locale 		= 'tr';
		}else {
			$locale  		= 'en';
		}

		$payment_id         = $query['payment_id'];
		$remoteIpAddr       = $this->request->server['REMOTE_ADDR'] ;
		$apiKey             = $this->config->get('payment_iyzico_api_key');
		$secretKey          = $this->config->get('payment_iyzico_secret_key');
		$rand               = rand(100000,99999999);


		$response           = $this->cancelPayment($locale, $payment_id, $remoteIpAddr, $apiKey, $secretKey, $rand, $order_id);

		if ($response->status == "failure") {
				$data['message'] = 'Hata Kodu '.$response->errorCode.' Hata '.$response->errorMessage;
		}

		if(isset($response->status) && $response->status == 'success'){

			$response_status            = $response->status;
			$response_conversationId    = $response->conversationId;
			$response_paymentId         = $response->paymentId;
			$response_price             = $response->price;
			$response_authCode          = $response->authCode;
			$response_hostReference     = $response->hostReference;
			$response_currency          = $response->currency;
			$response_request_type      = 'order_cancel'; //bu öenmli
			$response_date_created      = date('Y-m-d H:i:s');
			$response_date_created_tr   = date('d-m-d H:i:s');
			$response_note              = 'Siparişinizin <b>'.$response_price.' '.$response_currency.'</b> Tutarındaki Ödemesi <b>'.$response_date_created_tr.'</b> tarihinde <b>İptal Edildi</b>, Ödeme Tarafınıza Aktarılacaktır.';


			$iyzico_total_refunded = $response_price;
			$iyzico_response       = array(
				"response_status"         => $response_status, 
				"response_conversationId" => $response_conversationId,
				"response_paymentId"      => $response_paymentId,
				"response_price"          => $response_price,
				"response_authCode"       => $response_authCode,
				"response_hostReference"  => $response_hostReference,
				"response_currency"       => $response_currency,
				"response_request_type"   => $response_request_type,
				"response_date_created"   => $response_date_created,
				"response_note"           => $response_note,
			);
			$iyzico_response              = json_encode($iyzico_response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			$update_refund_query_string   = "UPDATE  " . DB_PREFIX . "iyzico_order_refunds 
			SET  iyzico_total_refunded  = '{$iyzico_total_refunded}', iyzico_response  = '{$iyzico_response}' 
			WHERE  iyzico_order_id  = '{$order_id}' ";

			$this->db->query($update_refund_query_string);

			$data['message']         = $response_note;

			$data_temp = array(
				'order_status_id' => 4,//İptal Edildi
				'notify'          => 1,
				'override'        => 0,
				'comment'         => ''.$response_note.''
			);

			$this->addOrderHistory($order_id, $data_temp,$store_id = 0);
			$data['success']   = true;
		}
			
			}
			
			
           
        } catch (\Exception $ex) {
             $data['message']   = $ex->getMessage();
			 $data['success']   = false;
        }
		
		 echo json_encode($data);
       
    }

    public function refund() {

        $order_id = $this->request->post['order_id'];
        $data     = array();
		
        try {

            if (!$order_id) {
				
                $data['message']   = 'Geçersiz Sipariş';
		$data['success']   = false;
				
            }else{

				$this->load->model('sale/order');
				$this->load->model('extension/payment/iyzico');
				
				$item_id       = (int) $this->request->post['item_id'];
				$amount        = (double) $this->request->post['amount'];
				$order_details = $this->model_sale_order->getOrder($order_id);

				if (!$order_details) {
					
					$data['message']   = 'Geçersiz Sipariş';
					$data['success']   = false;
					
				}else{

					$refunded_transactions_query_string = "SELECT * FROM  " . DB_PREFIX . "iyzico_order_refunds   WHERE `iyzico_order_id` = '{$order_id}' AND iyzico_item_id  = '{$item_id}'";

					$refunded_transactions_query        = $this->db->query($refunded_transactions_query_string);

					$refund_data      = $refunded_transactions_query->row;
					$remaining_amount = (double) $refund_data['iyzico_item_paid_price'] - (double) $refund_data['iyzico_total_refunded'];
					$diff             = (string) $amount - (string) $remaining_amount;
					
					if ($diff > 0) {
					   
						 $data['message']   = 'Sipariş tutarı '.$remaining_amount.' dan büyük olamaz.';
						 $data['success']   = false;
						 
					}else{

					
						if(empty($language) or $language == 'null')
						{
							$locale  		= $this->language->get('code');
							
						}elseif ($language == 'TR' or $language == 'tr') {
							
							$locale 		= 'tr';
							
						}else {
							
							$locale  		= 'en';
							
						}
						
						$payment_id         = $refund_data['iyzico_paymentId'];
						$remoteIpAddr       = $this->request->server['REMOTE_ADDR'] ;
						$apiKey             = $this->config->get('payment_iyzico_api_key');
						$secretKey          = $this->config->get('payment_iyzico_secret_key');
						

						$rand               = rand(100000,99999999);

						$response           = $this->refundPayment($locale, $payment_id, $remoteIpAddr, $apiKey, $secretKey, $rand, $order_id, $amount, $order_details['currency_code'],$refund_data['iyzico_paymentTransactionId']);
						
						
				if ($response->status == "failure") {
						$data['message'] = 'Hata Kodu '.$response->errorCode.' Hata '.$response->errorMessage;
				}
				
	
				if(isset($response->status) && $response->status == 'success'){
					
					
					$this->load->model('sale/order');
					$order_info        = $this->model_sale_order->getOrder($order_id);
					$language_id       = $order_info['language_id'];
					$order_status_id   = $order_info['order_status_id'];

					$response_locale               = $response->locale;
					$response_status               = $response->status;
					$response_conversationId       = $response->conversationId;
					$response_paymentId            = $response->paymentId;
					$response_paymentTransactionId = $response->paymentTransactionId;
					$response_price                = $response->price;
					$response_authCode             = $response->authCode;
					$response_hostReference        = $response->hostReference;
					$response_currency             = $response->currency;
					$response_request_type         = 'order_refund'; //bu öenmli
					$response_date_created         = date('Y-m-d H:i:s');
					$response_date_created_tr      = date('d-m-Y H:i:s');
					
					
					$product_data_query    = $this->db->query("SELECT * FROM  " . DB_PREFIX . "product_description   WHERE  product_id  = '{$item_id}' AND  language_id = '{$language_id}'");
					$product_data          = $product_data_query->row;

					$amount_formated       = $this->currency->format($amount, $order_details['currency_code'], "1");
					
					$response_note         = 'Siparişinizin <b>'.$this->escape($product_data['name']).' isimli ürünün  '.$amount_formated.'</b> Tutarındaki Ödemesi <b>'.$response_date_created_tr.'</b> tarihinde <b>İade Edildi</b>, Ödeme Tarafınıza Aktarılacaktır.';
					
					$iyzico_total_refunded = $response_price;
					$iyzico_response       = array(
						"response_status"         => $response_status, 
						"response_conversationId" => $response_conversationId,
						"response_paymentId"      => $response_paymentId,
						"response_price"          => $response_price,
						"response_authCode"       => $response_authCode,
						"response_hostReference"  => $response_hostReference,
						"response_currency"       => $response_currency,
						"response_request_type"   => $response_request_type,
						"response_date_created"   => $response_date_created,
						"response_note"           => $response_note,
					);
					$iyzico_response              = json_encode($iyzico_response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
					$update_refund_query_string   = "UPDATE  " . DB_PREFIX . "iyzico_order_refunds SET  iyzico_total_refunded  = '{$iyzico_total_refunded}', iyzico_response  = '{$iyzico_response}' WHERE  iyzico_order_id  = '{$order_id}'  AND iyzico_item_id  = '{$item_id}' ";

					$this->db->query($update_refund_query_string);
					
					$data['message']         = $response_note;
				
					$data_temp = array(
						'order_status_id' => $order_status_id,//İptal Edildi
						'notify'          => 1,
						'override'        => 0,
						'comment'         => ''.$response_note.''
					);

					$this->addOrderHistory($order_id, $data_temp,$store_id = 0);
					$data['success']   = true;
				}	
						
						
						
					}
					
				}
			
			}
			
			
			
			 
        } catch (\Exception $ex) {
            $data['message'] = $ex->getMessage();
        }

        echo json_encode($data);
    }

    public function escape($raw)
    {
        $flags = ENT_QUOTES;

        // HHVM has all constants defined, but only ENT_IGNORE
        // works at the moment
        if (defined("ENT_SUBSTITUTE") && !defined("HHVM_VERSION")) {
            $flags |= ENT_SUBSTITUTE;
        } else {
            // This is for 5.3.
            // The documentation warns of a potential security issue,
            // but it seems it does not apply in our case, because
            // we do not blacklist anything anywhere.
            $flags |= ENT_IGNORE;
        }

        $raw = str_replace(chr(9), '    ', $raw);

        return htmlspecialchars($raw, $flags, "UTF-8");
    }

    private function addOrderHistory($order_id, $data_temp,$store_id = 0)
    {
      
        $order_status_id = $data_temp['order_status_id'];
        $notify          = $data_temp['notify'];
        $override        = $data_temp['override'];
        $comment         = ''.$order_id.' no lu '.$data_temp['comment'];
        

        $sql = "UPDATE ".DB_PREFIX."order SET order_status_id = '$order_status_id' WHERE order_id = " . $order_id;
        $this->db->query($sql);

        $sql = "INSERT INTO ".DB_PREFIX."order_history (order_id, order_status_id, notify, comment, date_added) VALUES ( " . $order_id . ", $order_status_id,  $notify, '$comment', CURRENT_TIMESTAMP)";
        $this->db->query($sql);
		
		$this->load->model('sale/order');
		$order_info    = $this->model_sale_order->getOrder($order_id);
		$email         = $order_info['email'];
		
		if($email){
		$config_name = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');
		$setSubject  = "$order_id no lu siparişinizin durumu değişti | $config_name";
		$setSender   = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');
		$datas       = array(
			
			"setTo"              => ''.$email.'',
			"setFrom"            => ''.$this->config->get('config_email').'',
			"setSender"          => ''.$setSender.'',
			"setSubject"         => ''.$setSubject.'',
			"setText"            => ''.html_entity_decode($comment, ENT_QUOTES, 'UTF-8').''
		
		);

		$this->psmail->defaultMailSend($datas);
		
		}
		
		
    }




}

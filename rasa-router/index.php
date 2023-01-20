<?php

/* Config */

$rasa_url = 'http://localhost:5005';
$chatwoot_url = 'http://localhost:3000';
$chatwoot_bot_token = 'rRwh3nuXUX36xQDgxUksg46e';


$json = file_get_contents('php://input');
error_log("request payload: #{$json}", 0);

$data = json_decode($json);
$message_type = $data->message_type;
$message = $data->content;
$conversation = $data->conversation->id;
$contact = $data->sender->id;
$account = $data->account->id;

error_log("message_type: {$message_type}", 0);
error_log("message: {$message}", 0);
error_log("conversation: {$conversation}", 0);
error_log("contact: {$contact}", 0);
error_log("account: {$account}", 0);
 
if($message_type == "incoming")
{  
  error_log("sending message to bot: {$message}", 0);
  $bot_response = send_to_bot($contact, $message);

  error_log("bot response",0);
  error_log(json_encode($bot_response),0);

  $message2 = null;
  if (property_exists($bot_response, 'custom')) { 
	  $message2 = $bot_response->custom->payload;
	  error_log("message2",0);
	  error_log(json_encode($message2),0);
	$create_message = send_to_chatwoot_custom($account, $conversation, $message2);
  }else{
	$message2 = $bot_response->text;
        error_log("bot replied: {$message2}", 0);
	$create_message = send_to_chatwoot($account, $conversation, $message2);
  }
}


function send_to_bot($sender, $message){
  global $rasa_url;
  $url = "{$rasa_url}/webhooks/rest/webhook";
  $data = array('sender' => $sender, 'message' => $message);

  $options = array(
  'http' => array(
      'method'  => 'POST',
      'content' => json_encode( $data ),
      'header'=>  "Content-Type: application/json\r\n" .
                  "Accept: application/json\r\n"
      )
  );

  $context  = stream_context_create( $options );
  $result = file_get_contents( $url, false, $context );

  $response = json_decode($result);

  return $response[0];
}

 function send_to_chatwoot_custom($account, $conversation, $message){
    global $chatwoot_url, $chatwoot_bot_token; 
    $url = "{$chatwoot_url}/api/v1/accounts/{$account}/conversations/{$conversation}/messages";
  
    $options = array(
    'http' => array(
        'method'  => 'POST',
        'content' => json_encode( $message ),
        'header'=>  "Content-Type: application/json\r\n" .
                    "Accept: application/json\r\n" .
                    "api_access_token: {$chatwoot_bot_token}"
        )
    );
     $context  = stream_context_create( $options );
     $result = file_get_contents( $url, false, $context );
     error_log("chatwoot response: {$result}",0);
     $response = json_decode($result);
     return $result;

 }

function send_to_chatwoot($account, $conversation, $message){
  global $chatwoot_url, $chatwoot_bot_token; 
  $url = "{$chatwoot_url}/api/v1/accounts/{$account}/conversations/{$conversation}/messages";
  $data = array('content' => $message);

  $options = array(
  'http' => array(
      'method'  => 'POST',
      'content' => json_encode( $data ),
      'header'=>  "Content-Type: application/json\r\n" .
                  "Accept: application/json\r\n" .
                  "api_access_token: {$chatwoot_bot_token}"
      )
  );

  $context  = stream_context_create( $options );
  $result = file_get_contents( $url, false, $context );
  error_log("chatwoot response: {$result}",0);
  $response = json_decode($result);
  return $result;
}

?>

<?php

namespace App\GatewayWorker;

use GatewayWorker\Lib\Gateway;
use Illuminate\Support\Facades\Log;

class Events
{
    public static function onWorkerStart($businessWorker)
    {
        echo "BusinessWorker    Start\n";
    }

    public static function onConnect($client_id)
    {
        Gateway::sendToClient($client_id, json_encode(['type' => 'init', 'client_id' => $client_id]));
    }

    public static function onWebSocketConnect($client_id, $data)
    {

    }

    public static function onMessage($client_id, $message)
    {
        $response = ['errcode' => 0, 'msg' => 'ok', 'data' => []];
        Log::debug($message);
        $message = json_decode($message);

        if (!isset($message->mode)) {
            $response['msg'] = 'missing parameter mode';
            $response['errcode'] = 400;
            Gateway::sendToClient($client_id, json_encode($response));
            return false;
        }

        switch ($message->mode) {
            case 'say':   #处理发送的聊天
                $response['msg'] = 'hello';
                break;
            default:
                $response['errcode'] = 400;
                $response['msg'] = 'Undefined';
        }

        Gateway::sendToClient($client_id, json_encode($response));
    }

    public static function onClose($client_id)
    {
        Log::info('close connection' . $client_id);
    }
}

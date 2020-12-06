<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Log as LogModel;


class LogController extends Controller
{
    public static function buildLogPayload($log_model,$log_model_id,$log_type,$user_id,array $description){
        $payload['log_model'] = $log_model;
        $payload['log_model_id'] = $log_model_id;
        $payload['log_type'] = $log_type;
        $payload['user_id'] = $user_id;
        $payload['description'] = json_encode($description);
        return $payload;
    }

    public static function storeLog(array $param){
        $log = new LogModel;
        $log->log_model = isset($param['log_model']) ? $param['log_model'] : null;
        $log->log_model_id = isset($param['log_model_id']) ? $param['log_model_id'] : null;
        $log->log_type = isset($param['log_type']) ? $param['log_type'] : null;
        $log->user_id = isset($param['user_id']) ? $param['user_id'] : null;
        $log->description = isset($param['description']) ? $param['description'] : null;
        $log->save();
        return $log;
    }
}

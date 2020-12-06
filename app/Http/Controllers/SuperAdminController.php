<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\AdminRequestController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;

class SuperAdminController extends Controller
{
    public function adminRequests(Request $request){
        $request->validate([
            "user_id"   =>'integer',
            'is_active_request' =>'in:true,false'
        ]);
        $param =[];
        if(isset($request->user_id)){
            $param['user_id']   =  $request->user_id; 
        }
        if(isset($request->is_active_request)){
            $param['is_active_request']   =  $request->boolean('is_active_request'); 
        }
        $adminRequests = AdminRequestController::getAdminRequest($param);

        return $adminRequests;
    }

    public function approveRequest(Request $request, $request_id){
        $param['id']    =   $request_id;
        $param['is_active_request'] = true;
        $param['is_completed'] = false;
        $adminRequest = AdminRequestController::getOneAdminRequest($param);
        if(!$adminRequest) return response()->json(['msg' => 'No Active Request Found.'], 404);
        return response()->json(['msg' => 'Request Declined'], 200);
    }

    public function declineRequest(Request $request, $request_id){
        $param['id']    =   $request_id;
        $param['is_active_request'] = true;
        $param['is_completed'] = false;
        $adminRequest = AdminRequestController::getOneAdminRequest($param);
        if(!$adminRequest) return response()->json(['msg' => 'No Active Request Found.'], 404);
        AdminRequestController::changeAdminRequestStatus($request_id,false, $adminRequest->model);
        return $adminRequest;
    }

    private function handleAdminRequest($adminRequest){
        switch(trim($adminRequest->model)){
            case 'post':   
                $obj = new PostController;
                return $obj->handleAdminRequest($adminRequest);
            break;
            case 'user':
                $obj = new UserController;
                return $obj->handleAdminRequest($adminRequest);
            break;
            default: 
                return response()->json(['msg' => 'Invalid Request'], 404);
            break;

        }
        return $res;
    }

}

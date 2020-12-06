<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LogController;
use App\Models\User;
use App\Models\AdminRequest;
use App\Models\Log as LogModel;
use Illuminate\Support\Facades\DB;

use Log;


class AdminRequestController extends Controller
{
    public function editUser(Request $request, $user_id){
        $userExist = UserController::checkIfUserExist($user_id, ['user_type' => User::USER_TYPE_USER]);
        if(!$userExist) return response()->json(['msg' => 'Not Found.'], 404);
        $payload = $this->buildAdminRequest('user',$user_id, 'edit', $request->toArray(), $request->user()->id);
        
        try{
            DB::beginTransaction();
            $adminRequest = $this->storeAdminRequest($payload);
            $logPayload = LogController::buildLogPayload('user',$adminRequest->id,LogModel::LOG_TYPE_ACTION,$request->user()->id,['msg' => 'Admin requested to edit user details','data' => $request->toArray()]);
            LogController::storeLog($logPayload);
            DB::commit();
            return response()->json(['msg' => 'Request Submitted.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $e->getMessage();
        }
    }

    public function createPost(Request $request, $user_id){
        $validatedData = $request->validate([
            'body' => 'required'
        ]);
        $userExist = UserController::checkIfUserExist($user_id, ['user_type' => User::USER_TYPE_USER]);
        if(!$userExist) return response()->json(['msg' => 'Not Found.'], 404);
        $validatedData['user_id'] = $user_id;
        $payload = $this->buildAdminRequest('post',null, 'create', $validatedData, $request->user()->id);
        try{
            DB::beginTransaction();
            $adminRequest = $this->storeAdminRequest($payload);
            $logPayload = LogController::buildLogPayload('post',$adminRequest->id,LogModel::LOG_TYPE_ACTION,$request->user()->id,['msg' => 'Admin requested to create a post','data' => $request->toArray()]);
            LogController::storeLog($logPayload);
            DB::commit();
            return response()->json(['msg' => 'Request Submitted.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $e->getMessage();
        }
       
    }

    public function editPost(Request $request, $user_id){
        $validatedData = $request->validate([
            'body' => 'required',
            'post_id' => 'required',
        ]);

        $userExist = UserController::checkIfUserExist($user_id, ['user_type' => User::USER_TYPE_USER]);
        if(!$userExist) return response()->json(['msg' => 'User Not Found.'], 404);

        $postExist = PostController::checkIfPostExist($request->post_id, ['user_id' => $user_id]);
        if(!$postExist) return response()->json(['msg' => 'Post Not Found.'], 404);

        $payload = $this->buildAdminRequest('post',$request->post_id, 'edit', $validatedData, $request->user()->id);
        try{
            DB::beginTransaction();
            $adminRequest = $this->storeAdminRequest($payload);
            $logPayload = LogController::buildLogPayload('post',$adminRequest->id,LogModel::LOG_TYPE_ACTION,$request->user()->id,['msg' => 'Admin requested to edit a post','data' => $request->toArray()]);
            LogController::storeLog($logPayload);
            DB::commit();
            return response()->json(['msg' => 'Request Submitted.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $e->getMessage();
        }
    }

    public function deletePost(Request $request, $user_id){
        $validatedData = $request->validate([
            'post_id' => 'required|integer'
        ]);
        $userExist = UserController::checkIfUserExist($user_id, ['user_type' => User::USER_TYPE_USER]);
        if(!$userExist) return response()->json(['msg' => 'User Not Found.'], 404);

        $postExist = PostController::checkIfPostExist($request->post_id, ['user_id' => $user_id]);
        if(!$postExist) return response()->json(['msg' => 'Post Not Found.'], 404);
        $payload = $this->buildAdminRequest('post',$request->post_id, 'delete', null, $request->user()->id);
        try{
            DB::beginTransaction();
            $adminRequest = $this->storeAdminRequest($payload);
            $logPayload = LogController::buildLogPayload('post',$adminRequest->id,LogModel::LOG_TYPE_ACTION,$request->user()->id,['msg' => 'Admin requested to delete a post','data' => $request->toArray()]);
            LogController::storeLog($logPayload);
            DB::commit();
            return response()->json(['msg' => 'Request Submitted.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $e->getMessage();
        }
        
    }

    private function storeAdminRequest($payload){
        if(isset($payload['request_model'])  && isset($payload['requested_model_id']) && !empty($payload['requested_model_id'])){
            $this->disableAllActiveRequestForTheModel($payload['request_model'], $payload['requested_model_id']);
        }

        $adminRequest = new AdminRequest;
        $adminRequest->model = isset($payload['request_model']) ? $payload['request_model'] : null;
        $adminRequest->requested_model_id = isset($payload['requested_model_id']) ? $payload['requested_model_id'] : null;
        $adminRequest->request_type = isset($payload['request_type']) ? $payload['request_type'] : null;
        $adminRequest->requested_data = isset($payload['requested_data']) ? json_encode($payload['requested_data']) : null;
        $adminRequest->user_id = isset($payload['user_id']) ? json_encode($payload['user_id']) : null;
        $adminRequest->is_active_request = true;
        $adminRequest->is_completed = false;
        $adminRequest->save();
        return $adminRequest;
    }

    public function disableAllActiveRequestForTheModel($model, $id){
        AdminRequest::where('model', $model)
                        ->where('requested_model_id', $id)
                        ->where('is_completed', false)
                        ->update(['is_active_request' => false]);

    }

    private function buildAdminRequest($request_model,$requested_model_id, $request_type,$requested_data, $user_id){

        $payload['request_model'] = trim($request_model);
        $payload['requested_model_id'] = $requested_model_id;
        $payload['request_type'] = trim($request_type);
        $payload['requested_data'] = $requested_data;
        $payload['user_id'] = $user_id;

        return $payload;
    }

    public static function getAdminRequest(array $param=[]){
        $query = AdminRequest::orderBy('id', 'DESC');
        if(isset($param['user_id'])) $query->where('user_id', $param['user_id']);
        if(isset($param['is_active_request'])) $query->where('is_active_request', $param['is_active_request']);
        return $query->simplePaginate(10);
    }

    public static function countAdminRequest(array $param){
        $query = AdminRequest::orderBy('id', 'DESC');
        if(isset($param['user_id'])) $query->where('user_id', $param['user_id']);
        if(isset($param['start_time'])) $query->where('created_at', '>=', $param['start_time']);
        if(isset($param['end_time'])) $query->where('created_at', '<=', $param['end_time']);
        return $query->count();
    }

    public static function getOneAdminRequest($param){
        $query = AdminRequest::orderBy('id', 'DESC');
        if(isset($param['id'])) $query->where('id', $param['id']);
        if(isset($param['user_id'])) $query->where('user_id', $param['user_id']);
        if(isset($param['is_completed'])) $query->where('is_completed', $param['is_completed']);
        if(isset($param['is_active_request'])) $query->where('is_active_request', $param['is_active_request']);
        return $query->first();

    }

    public static function changeAdminRequestStatus($id,bool $is_completed, $log_model){
        $adminRequest = AdminRequest::find($id);
        if(!empty($adminRequest)){
            $adminRequest->is_completed = $is_completed;
            $adminRequest->is_active_request = false;
            $adminRequest->save();
            
            $string = $is_completed ? 'approved' : 'declined';
            $data = ["id"   =>$id, "is_completed"   => $is_completed];
            $logPayload = LogController::buildLogPayload($log_model,$adminRequest->id,LogModel::LOG_TYPE_AUDIT,\Auth::user()->id,['msg' => "Admin $string a request",'data' => $data]);
            LogController::storeLog($logPayload);
            return $adminRequest;
        }
        return null;
    }

    public static function getRequestFrequency(Request $request, $user_id){
        $userExist = UserController::checkIfUserExist($user_id, ['user_type' => User::USER_TYPE_ADMIN]);
        if(!$userExist) return response()->json(['msg' => 'Admin Not Found.'], 404);
        $validatedData = $request->validate([
            "start_time"    =>'required|date',
            "end_time"    =>'required|date',
        ]);
        $validatedData['user_id']   =    $user_id;
        return ['request_count' => self::countAdminRequest($validatedData)];

    }
}

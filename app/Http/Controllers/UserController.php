<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;


class UserController extends Controller
{
    public static function checkIfUserExist($id, $param =[]){
        $query =  User::where('id', $id);
        if(!empty($param)){
            if(isset($param['user_type']))
                $query->where('user_type', $param['user_type']);
            
        }
        return $query->count() > 0 ? true : false;
    }

    public function handleAdminRequest($adminRequest){
        switch(trim($adminRequest->request_type)){
            case 'edit' : 
               return $this->handleEditAdminRequest($adminRequest);
            break;
            default:
            return response()->json(['msg' => 'Unprocessible Request.'], 422);
            break;

        }   
    }

    private function edit($id, $param){
        $user = User::find($id);
        if(empty($user)) return null;
        if(isset($param['name']))  $user->name = $param['name'];
        if(isset($param['email']))  $user->email = $param['email'];
        $user->save();
        return $user;
    }

    private function handleEditAdminRequest($adminRequest){
        $data =  json_decode($adminRequest->requested_data,true);
        if(isset($data['name']) || isset($data['email'])){
            try{
                \DB::beginTransaction();
                $post = $this->edit($adminRequest->requested_model_id,$data);
                AdminRequestController::changeAdminRequestStatus($adminRequest->id,true, 'post');
                \DB::commit();
                return response()->json(['msg' => 'Request Approved.'], 200);
            } catch (\Exception $e) {
                \DB::rollBack();
                return $e->getMessage();
            }
        }
        return response()->json(['msg' => 'Invalid Request.'], 422);
    }
}

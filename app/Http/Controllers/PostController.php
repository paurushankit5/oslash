<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\User;
use App\Http\Controllers\AdminRequestController;

class PostController extends Controller
{
    public function storePost(Request $request){
        $validatedData = $request->validate([
            'body' => 'required'
        ]);

        $validatedData['user_id'] = $request->user()->id;
        $post = $this->store($validatedData);
        return $post;
    }

    public function getPosts(Request $request){
        return $this->get($request->user()->id);
    }

    public function getOnePost(Request $request, $post_id){
        $post = $this->findOne($post_id, $request->user()->id);
        if(empty($post)) return response()->json(['msg' => 'Not Found.'], 404);
        return $post;
    }

    public function deletePost(Request $request, $post_id){
        $status =  $this->delete($request->post_id, $request->user()->id);
        return ['status' => $status];
    }

    private function delete($id, $user_id = null){
        $query =  Post::where('id', $id);
        if($user_id != null) $query->where('user_id', $user_id);
        if($query->count() > 0){
            $query->first()->delete();
            $obj = new AdminRequestController;
            $obj->disableAllActiveRequestForTheModel('post', $id);
            return true;
        }
        return false;
    }

    private function get($user_id){
        $query = Post::where('user_id', $user_id)->orderBy('id','DESC');
        return $query->simplePaginate(10);
    }

    private function findOne($post_id, $user_id = null){
        $query = Post::where('id', $post_id);
        if($user_id != null) $query->where('user_id', $user_id);
        return $query->first();
    }


    private function store($param){
        $post = new Post;
        $post->body = isset($param['body']) ? $param['body'] : null;
        $post->user_id = isset($param['user_id']) ? $param['user_id'] : null;
        $post->save();
        return $post;
    }

    private function edit($id, $param){
        $post = Post::find($id);
        if(empty($post)) return null;
        $post->body = isset($param['body']) ? $param['body'] : null;
        $post->save();
        return $post;
    }

    public static function checkIfPostExist($id, $param =[]){
        $query =  Post::where('id', $id);
        if(!empty($param)){
            if(isset($param['user_id']))
                $query->where('user_id', $param['user_id']);
            
        }
        return $query->count() > 0 ? true : false;
    }

    public static function countPost(array $param){
        $query =  Post::orderBy('id','DESC');
        if(isset($param['user_id'])) $query->where('user_id', $param['user_id']);
        if(isset($param['start_time'])) $query->where('created_at', '>=', $param['start_time']);
        if(isset($param['end_time'])) $query->where('created_at', '<=', $param['end_time']);
            
        return $query->count();
    }

    public function handleAdminRequest($adminRequest){
        switch(trim($adminRequest->request_type)){
            case 'create' : 
               return $this->handleCreateAdminRequest($adminRequest);
            break;
            case 'edit' : 
               return $this->handleEditAdminRequest($adminRequest);
            break;
            case 'delete' : 
                return $this->handleDeleteAdminRequest($adminRequest);
            break;
            default:
            return response()->json(['msg' => 'Unprocessible Request.'], 422);
            break;

        }  
    }

    private function handleDeleteAdminRequest($adminRequest){
        if(!empty($adminRequest->requested_model_id)){
            try{
                \DB::beginTransaction();
                $post = $this->delete($adminRequest->requested_model_id);
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

    private function handleEditAdminRequest($adminRequest){
        $data =  json_decode($adminRequest->requested_data,true);
        if(isset($data['body']) && isset($data['post_id'])){
            try{
                \DB::beginTransaction();
                $post = $this->edit($data['post_id'],['body' => $data['body']]);
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

    private function handleCreateAdminRequest($adminRequest){
        $data =  json_decode($adminRequest->requested_data,true);
        if(isset($data['body']) && isset($data['user_id'])){
            try{
                \DB::beginTransaction();
                $post = $this->store($data);
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

    public function getPostFrequency(Request $request, $user_id){
        $userExist = UserController::checkIfUserExist($user_id, ['user_type' => User::USER_TYPE_USER]);
        if(!$userExist) return response()->json(['msg' => 'User Not Found.'], 404);
        $validatedData = $request->validate([
            "start_time"    =>'required|date',
            "end_time"    =>'required|date',
        ]);
        $validatedData['user_id']   =    $user_id;
        return ['post_count' => self::countPost($validatedData)];
    }
}

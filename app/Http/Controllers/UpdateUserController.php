<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserUpdateRequest;
use App\Services\DatabaseConnectionService;
use App\Services\HelperFunctions;
use Exception;
use Illuminate\Http\Request;

class UpdateUserController extends Controller
{
    /**
     * Take remember token and Credentials for update user profile
     * update Credentials
     * return success
     */
    function userUpdate(UserUpdateRequest $request){
        $data_to_update = [];
        foreach ($request->all() as $key => $value) {
            if (in_array($key, ['name', 'email', 'age','password','image'])) {
                if ($key == 'image') {
                    $fileName = HelperFunctions::base64Decoder($value);
                    $pathurl=$_SERVER['HTTP_HOST']."/api/storage/app/profileImage/".$fileName[0];
                    $path=storage_path('app\\profileImage').'\\'.$fileName[0];
                    file_put_contents($path,base64_decode($fileName[1]));
                    $value = $pathurl;
                }
                $data_to_update[$key]=$value;
            }
        }
        try{
            $collection = new DatabaseConnectionService();
            $conn = $collection->getConnection('users');
            $conn->updateOne(array('remember_token'=>$request->remember_token),
                                array('$set'=>$data_to_update));
        }catch(Exception $ex){
            return response()->json(['message' => $ex->getMessage()],422);
        }
        return response()->success();
    }
}

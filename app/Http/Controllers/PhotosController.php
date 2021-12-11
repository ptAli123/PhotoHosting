<?php

namespace App\Http\Controllers;

use App\Http\Requests\PhotoRequest;
use App\Services\DatabaseConnectionService;
use App\Services\HelperFunctions;
use Exception;
use Illuminate\Http\Request;

class PhotosController extends Controller
{
    /**
     * Take sign up Credentials
     * Save photo data in database
     * return success
     */
    function uploadPhoto(PhotoRequest $request) {
        try{
            $collection = new DatabaseConnectionService();
            $conn = $collection->getConnection('photos');
            $fileName = HelperFunctions::base64Decoder($request->photo);
            $pathurl=$_SERVER['HTTP_HOST']."/api/storage/app/photos/".$fileName[0];
            $path=storage_path('app\\photos').'\\'.$fileName[0];
            file_put_contents($path,base64_decode($fileName[1]));
            $imageArr = explode('.',$pathurl);
            $document = array(
                "user_id" => $request->data->_id,
                "date" => date("Y-m-d"),
                "time" => date("h:i:sa"),
                "name" => $request->imageName,
                "extensions" => $imageArr[4],
                "hidden" => 1,
                "private" => 0,
                "public" => 0,
                "photo" => $pathurl,
                );
            $conn->insertOne($document);
        }catch(Exception $ex){
            return response()->json(['message' => $ex->getMessage()],422);
        }
        return response()->success();
    }

   /**
     * Take photo link
     * Check photo is public or not
     * return show Photo
     */
    function accessPhoto(Request $request, $filename) {
        $path1 = $_SERVER['HTTP_HOST']."/photo/storage/app/photos/".$filename;
        try{
            $collection = new DatabaseConnectionService();
            $conn = $collection->getConnection('photos');
            $data = $conn->findOne(array("photo" => $path1));
        }catch(Exception $ex){
            return response()->json(['message' => $ex->getMessage()],422);
        }
        if ($data && $data['public'] == 1) {
            $headers = ["Cache-Control" => "no-store, no-cache, must-revalidate, max-age=0"];
            $path = storage_path("app/photos".'/'.$filename);
            if (file_exists($path)) {
                return response()->download($path, null, $headers, null);
            }
        } else {
            return response()->json(['message' => "you are not allowed"]);
        }
    }

    /**
     * Take photo Name that generate by
     * return show Photo or you are not allowed
     */

    function accessPhotoLogin(Request $request) {
        $headers = ["Cache-Control" => "no-store, no-cache, must-revalidate, max-age=0"];
        $path = storage_path("app/photos".'/'.$request->photoName);
        if (file_exists($path)) {
            return response()->download($path, null, $headers, null);
        }
    }

    /**
     * Take user id and photo id
     * Fetch photo document of that same user and photo
     * return photo link
     */
    function generateLink(Request $request) {
        try{
            $collection = new DatabaseConnectionService();
            $conn = $collection->getConnection('photos');
            $id = new \MongoDB\BSON\ObjectId($request->photo_id);
            $data = $conn->findOne(array('_id' => $id,"user_id" => $request->data->_id));
        }catch(Exception $ex){
            return response()->json(['message' => $ex->getMessage()],422);
        }
        return response()->json(["Link" => $data['photo']]);
    }
    /**
     * Take user id and photo id
     * Delete photo document of that same user and photo
     * return success message
     */

    function removePhoto(Request $request) {
        try{
            $collection = new DatabaseConnectionService();
            $conn = $collection->getConnection('photos');
            $id = new \MongoDB\BSON\ObjectId($request->photo_id);
            $conn->deleteOne(array('_id' => $id,"user_id" => $request->data->_id));
        }catch(Exception $ex){
            return response()->json(['message' => $ex->getMessage()],422);
        }
        return response()->success();
    }

    /**
     * Take user id
     * Fetch all photos of that user
     * return list of all photos
     */

    function listPhoto(Request $request) {
        try{
            $collection = new DatabaseConnectionService();
            $conn = $collection->getConnection('photos');
            $photos = $conn->find(['user_id' => $request->data->_id]);
            $photosArr = json_decode(json_encode($photos->toArray(),true));
        }catch(Exception $ex){
            return response()->json(['message' => $ex->getMessage()],422);
        }
        return response()->json($photosArr);
    }

    /**
     * Take search perameters
     * Fetch all sorted photos of that user
     * return list of photos
     */

    function searchPhoto(Request $request) {
        try{
            $collection = new DatabaseConnectionService();
            $conn = $collection->getConnection('photos');
            $searchPera = [];
            foreach ($request->all() as $key => $value) {
                if (in_array($key, ['date','time','name', 'extensions', 'hidden','private','public'])) {
                    $searchPera[$key]=$value;
                }
            }
            $searchPera['user_id'] = $request->data->_id;
            $photos = $conn->find($searchPera);
        }catch(Exception $ex){
            return response()->json(['message' => $ex->getMessage()],422);
        }
        $photosArr = json_decode(json_encode($photos->toArray(),true));
        return response()->json($photosArr);
    }

     /**
     * Take photo id
     * remove all embbeded mails
     * return
     */

    function removeMails($id) {
        try{
            $collection = new DatabaseConnectionService();
            $conn = $collection->getConnection('photos');
            $conn->updateOne(array('_id'=>$id),array('$unset'=>array('shared'=>'')));
        }catch(Exception $ex){
            return response()->json(['message' => $ex->getMessage()],422);
        }
    }

     /**
     * Take photo id and user id
     * Make photo hidden and remove all the embbeded mails
     * return success
     */

    function makePhotoHidden(Request $request) {
        try{
            $collection = new DatabaseConnectionService();
            $conn = $collection->getConnection('photos');
            $id = new \MongoDB\BSON\ObjectId($request->photo_id);
            $conn->updateOne(array("user_id"=>$request->data->_id,"_id" => $id),array('$set'=>array("public" => 0,"hidden"=>1,"private"=>0)));
            $this->removeMails($id);
        }catch(Exception $ex){
            return response()->json(['message' => $ex->getMessage()],422);
        }
        return response()->success();
    }

    /**
     * Take photo id and user id
     * Make photo public and remove all the embbeded mails
     * return success
     */

    function makePhotoPublic(Request $request) {
        try{
            $collection = new DatabaseConnectionService();
            $conn = $collection->getConnection('photos');
            $id = new \MongoDB\BSON\ObjectId($request->photo_id);
            $conn->updateOne(array("user_id"=>$request->data->_id,"_id" => $id),array('$set'=>array("public" => 1,"hidden"=>0,"private"=>0)));
            $this->removeMails($id);
        }catch(Exception $ex){
            return response()->json(['message' => $ex->getMessage()],422);
        }
        return response()->success();
    }

    /**
     * Take photo id, user id and user email by which you want to share photo
     * Make photo private and embbeded that email
     * return success
     */

    function makePhotoPrivate(Request $request) {
        try{
            $collection = new DatabaseConnectionService();
            $conn = $collection->getConnection('photos');
            $id = new \MongoDB\BSON\ObjectId($request->photo_id);
            $conn->updateOne(array("user_id"=>$request->data->_id,"_id" => $id),array('$set'=>array("public" => 0,"hidden"=>0,"private"=>1)));
            $Emails = explode(',',$request->email);
            $conn->updateOne(["user_id" => $request->data->_id,"_id" => $id], ['$push'=>["shared"=>["mail"=>$request->email]]]);
        }catch(Exception $ex){
            return response()->json(['message' => $ex->getMessage()],422);
        }
        return response()->success();
    }

    /**
     * Take photo id, user id and user email
     * Delete that embbeded email
     * return success
     */

    function removeSpecificPrivateMail(Request $request) {
        try{
            $collection = new DatabaseConnectionService();
            $conn = $collection->getConnection('photos');
            $id = new \MongoDB\BSON\ObjectId($request->photo_id);
            $conn->updateOne(["user_id"=>$request->data->_id,"_id" => $id, "shared.mail"=>$request->email,"private" => 1], ['$pull'=>["shared"=>["mail"=>$request->email]]]);
        }catch(Exception $ex){
            return response()->json(['message' => $ex->getMessage()],422);
        }
        return response()->success();
    }
}

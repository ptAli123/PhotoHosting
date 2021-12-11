<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\SignUpRequest;
use App\Jobs\MailJob;
use App\Jobs\Mailtable;
use App\Mail\VarificationMail;
use App\Services\DatabaseConnectionService;
use App\Services\HelperFunctions;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SignUpController extends Controller
{
    /**
     * Take sign up Credentials
     * save in database and send confirmation mail with otp
     * return send mail message
     */
    function signUp(SignUpRequest $request) {
        $varify_token=rand(100,100000);
        $collection = new DatabaseConnectionService();
        $conn = $collection->getConnection('users');
        $fileName = HelperFunctions::base64Decoder($request->image);
        $pathurl=$_SERVER['HTTP_HOST']."/api/storage/app/profileImage/".$fileName[0];
        $path=storage_path('app\\profileImage').'\\'.$fileName[0];
        file_put_contents($path,base64_decode($fileName[1]));
        $document = array(
            "name" => $request->name,
            "email" => $request->email,
            "image" => $pathurl,
            "password"=> hash::make($request->password),
            "age"=>$request->age,
            "mail_verify_token" => $varify_token,
            "status" => 1
            );
        $conn->insertOne($document);
        $details = [
            'title' => 'confirmation Mail',
            'link' => $_SERVER['HTTP_HOST'].'/api/mail-confirmation/'.$request->email.'/'.$varify_token
        ];
        try{
            dispatch(new Mailtable($request->email,$details));
            //Mail::to($request->email)->send(new VarificationMail($details));
        }catch(Exception $ex){
            return response()->json(['message' => $ex->getMessage()],422);
        }
        return response()->json(["message"=>"mail send...."]);
    }
}

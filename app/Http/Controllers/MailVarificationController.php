<?php

namespace App\Http\Controllers;

use App\Services\DatabaseConnectionService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MailVarificationController extends Controller
{
    /**
     * Take email and varify token
     * verify otp token and email
     * set email verified = 1 in database
     * return success
     */
    function confirmed($email,$varify_token){
        try{
            $collection = new DatabaseConnectionService();
            $conn = $collection->getConnection('users');
            $conn->updateOne(array("email"=>$email,"mail_verify_token"=>(int)$varify_token),
                  array('$set'=>array("email_verified" => 1)));
        }catch(Exception $ex){
            return response()->json(['message' => $ex->getMessage()],422);
        }
        return response()->success();
    }
}

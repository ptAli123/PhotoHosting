<?php

namespace App\Http\Middleware;

use App\Services\DatabaseConnectionService;
use Closure;
use Exception;
use Illuminate\Http\Request;

class PrivateAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $filename = explode('/',$request->filename);
        try{
            $collection = new DatabaseConnectionService();
            $conn = $collection->getConnection('photos');
            $data = $conn->findOne(array("photo" => $request->filename));
        }catch(Exception $ex){
            return response()->json(['message' => $ex->getMessage()],422);
        }
        if ($data) {
            if ($data['private'] == 1) {
                $conn = $collection->getConnection('users');
                $mail = $conn->findOne(["remember_token" => $request->remember_token]);
                $conn = $collection->getConnection('photos');
                $data1 = $conn->findOne(["_id" => $data['_id'],"shared.mail" => $mail['email']]);
                if ($data1) {
                    return $next($request->merge(["data" => $data,"photoName" => $filename[5]]));
                } else {
                    return response()->json(['message' => "you are not allowed"]);
                }
            } else if ($data['hidden'] == 1) {
                
                $id = $conn->findOne(["photo" => $request->filename]);
                $conn = $collection->getConnection('users');
                $id2 = $conn->findOne(["remember_token" => $request->remember_token]);
                if ($id['user_id'] == $id2['_id']) {
                    return $next($request->merge(["data" => $data,"photoName" => $filename[5]]));
                } else {
                    return response()->json(['message' => "you are not allowed"]);
                }
            }
        }
    }
}

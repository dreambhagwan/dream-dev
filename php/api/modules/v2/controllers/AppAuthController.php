<?php
namespace api\modules\v2\controllers;

use common\models\LoginForm;
use common\models\User;
use yii\helpers\Json;
use api\modules\v1\modules\users\models\AuthToken;

class AppAuthController extends \common\controllers\cController
{
    private $cError;
    
    public function actionLogin()
    {
        $response =  [ "status" => 0 , "code" => 400 , "message" => "Bad request!" ];
        if( \Yii::$app->request->isPost ){
            //$response =  [ "status" => 0 , "error" => [ "code" => 400 , "message" => "Bad request!" ] ];
            $data = json_decode( file_get_contents('php://input') , JSON_FORCE_OBJECT );
            //echo '<pre>';print_r($data);die;
            if( json_last_error() == JSON_ERROR_NONE ){
                //if( isset( $data["LoginForm"][ "g_recaptcha_response" ] ) ){
                    //$g_url        = 'https://www.google.com/recaptcha/api/siteverify';
                    //$g_secret     = "6Lda0F4UAAAAAKO733L2JzZjdWKuHL5Kd_mWYkle";
                    //$g_recaptcha  = $data["LoginForm"]["g_recaptcha_response"];
                    
                    //unset( $data["LoginForm"][ "g_recaptcha_response" ] );
                    
                    /*$post_data = http_build_query(
                        array(
                            'secret'    => $g_secret,
                            'response'  => $g_recaptcha,
                            'remoteip'  => $_SERVER['REMOTE_ADDR']
                        )
                    );
                    
                    $opts = array('http' =>
                        array(
                            'method'  => 'POST',
                            'header'  => 'Content-type: application/x-www-form-urlencoded',
                            'content' => $post_data
                        )
                    );
                    
                    $g_context    = stream_context_create($opts);
                    $g_response   = file_get_contents( $g_url , false, $g_context);
                    $g_result     = json_decode($g_response);
                    
                    $response[ "error" ][ "data" ] = $g_result;
                    */
                    //if ($g_result->success) {
                        $model = new LoginForm();
                        $Ndata = [];
                        $Ndata["LoginForm"] = $data;
                        
                        if ($model->load( $Ndata ) ) {
                            if( $model->login() ){
                                $type = "user";
                                $roles = \Yii::$app->authManager->getRolesByUser( \Yii::$app->user->id );
                                
                                if( $roles != null ){
                                    foreach ( $roles as $role => $rData ){
                                        $type = $role;
                                    }
                                }
                                
                                $cData = [];
                                
                                $user = User::findOne(\Yii::$app->user->id);
                                
                                if( $user->role == 1 ){
                                    
                                    $response =  [
                                        "status" => 0 ,
                                        "message" => "Login failed, invalid user !" ,
                                    ];
                                    return $response;
                                    
                                }
                                
                                $auth_token = $this->renewAuthToken( \Yii::$app->user->identity );
                                
                                if( $auth_token != "" ){
                                    
                                    $user->is_login = 1;
                                    
                                    if( $user->save(['is_login']) ){
                                        
                                        $response =  [
                                            "status" => 1 ,
                                            "data" => [
                                                "username"              => strtolower( $model->username ) ,
                                                //"email"             => \Yii::$app->user->identity->email ,
                                                "token"                 => $auth_token ,
                                                "is_password_updated"   => $user->is_password_updated ,
                                                "role"   => $user->role, // add by jayesh
                                                //"allowed_resources" => $this->getAllowedResources( $type )
                                            ] ,
                                            "success" => [ "message" => "Logged In successfully!" ]
                                        ];
                                        
                                    }else{
                                        
                                        $response =  [
                                            "status" => 0 ,
                                            "message" => "login failed , please try again or contact admin!" ,
                                        ];
                                        
                                    }
                                }else{
                                    $response =  [
                                        "status" => 0 ,
                                        "message" => "login failed , please try again or contact admin!" ,
                                    ];
                                }
                            }else{
                                $response =  [ "status" => 0 , "error" => [ "message" => "Incorrect username or password !" ] ,"message" => "Incorrect username or password !" ];
                            }
                        }
                    //}
                //}
            }
            
            return $response;
        }else{
            return $response;
        }
    }
    
    public function actionChangePassword(){
        
        if( \Yii::$app->request->isPost ){
            $response =  [ "status" => 0 , "error" => [ "code" => 400 , "message" => "Bad request!" ] ];
            $data = json_decode( file_get_contents('php://input') , JSON_FORCE_OBJECT );
            //echo '<pre>';print_r($data);die;
            if( json_last_error() == JSON_ERROR_NONE ){
                
                $user = User::findOne( [ 'id' => \Yii::$app->user->id , 'role' => 4 , 'status' => 1 ] );
                
                if( $user != null ){
                    
                    if ( $user->validatePassword( $data['oldpassword'] )) {
                        
                        $user->is_password_updated = 1;
                        
                        $user->auth_key = \Yii::$app->security->generateRandomString( 32 );
                        $user->password_hash = \Yii::$app->security->generatePasswordHash( $data['password'] );
                        
                        if( $user->save( [ 'password' , 'auth_key','is_password_updated' ] ) ){
                            $response =  [ "status" => 1 , "success" => [ "message" => "Password changed successfully" ] ];
                        }else{
                            $response =  [ "status" => 0 , "error" => $user->errors ];
                        }
                    }else{
                        $response =  [ "status" => 0 , "error" => 'Incorrect old password.' ];
                    }
                }
            }
            
            return $response;
        }else{
            return [ "status" => 0 , "error" => [ "code" => 400 , "message" => "Bad request!" ] ];
        }
        
    }
    
    private function renewAuthToken( $user ){
       $auth_model = AuthToken::find()->where( [ "user_id" => $user->id , "user_type" => 1 ] )->one();
       
       $auth_token = $this->generateAuthTokenForCms( $user );
       $expired_on = time() + ( 24 * 60 * 60 );
       
       if( $auth_model != null ){
           $auth_model->token = $auth_token;
           $auth_model->expired_on = $expired_on;
           if( $auth_model->validate( [ "token" , "expired_on" ] ) ){
               if( $auth_model->save( [ "token" , "expired_on" ] ) ){
                   return $auth_token;
               }else{
                   $this->cError = $auth_model->errors;
               }
           }else{
               $this->cError = $auth_model->errors;
           }
       }else{
           $auth_model = new AuthToken();
           
           $auth_model->user_id = $user->id;
           $auth_model->user_type = 1;
           $auth_model->token = $auth_token;
           $auth_model->expired_on = $expired_on;
           
           if( $auth_model->validate() ){
               if( $auth_model->save() ){
                   return $auth_token;
               }else{
                   $this->cError = $auth_model->errors;
               }
           }else{
               $this->cError = $auth_model->errors;
           }
       }
       
       return "";
    }
    
    private function generateAuthTokenForCms( $user ){
        $hash_1 = md5( $user->username );
        $hash_2 = md5( $user->password_hash );
        $hash_3 = md5( $user->created_at );
        $hash_4 = md5( microtime() );
        $hash_5 = md5( "iConsent2" );
        
        $hash_f = [];
        $hash_f[] = hash( 'sha512' , $hash_1 . $hash_2 . $hash_3 . $hash_4 . $hash_5 );
        $hash_f[] = md5( $user->username . microtime() . "iConsent2"  );
        $hash_f[] = md5( $user->password_hash . microtime() . $user->created_at . "iConsent2"  );
        $hash_f[] = md5( "iConsent2-cms"  );
        
        return implode( ":" , $hash_f );
    }
    
    public function actionTest123(){
        // return $this->getAllowedResources( 1 );
    }
    
    private function getAllowedResources( $type ){
        $resources = [ ""  , "index" , "404" ];
        
        foreach ( $resources as $key => $value ){
            $resources[ $key ] = $this->encryptResourceUrl( $value );
        }
        
        if( $type == "admin" ){
            $routes = [
                "test" => [ "" ] ,
                "events" => [ 
                   "event"  => [ "" , "create" , "update" , "inplay" , "exchange" ] ,
                 ] ,
                "users" => [
                    "client"  => [ "" , "create" , "update" ] ,
                    "agent1" => [ "" , "create" , "update" ] ,
                    "agent2" => [ "" , "create" , "update" ]
                ],
                //"chips-allocation" => [ "" ] ,
                "chips-allocation" => [ 
                    "action"  => [ "" , "update" ] ,
                ] ,
               /* "chips" => [
                    "allocation"  => [ "" , "update" ] ,
                ] ,*/
                "rate-manipulation" => [ "" ] ,
            ];
        }else if( $type == "agent1" ){
            $routes = [
                "test" => [ "" ],
                "users" => [
                    "agent2" => [ "" , "create" , "update" ]
                ]
            ];
        }else if( $type == "agent2" ){
            $routes = [
                "test" => [ "" ] ,
                "users" => [
                    "client" => [ "" , "create" , "update" ]
                ]
            ];
        }else if( $type == "client" ){
            $routes = [
                "test" => [ "" ]
            ];
        }else{
            /* never goes here */
        }
        
        foreach ( $routes as $m => $mData ){
            $resources[] = $this->encryptResourceUrl( implode( '/' , [ $m ] ) );
            if( $mData != null ){
                foreach ( $mData as $c => $cData ){
                    $resources[] = $this->encryptResourceUrl( implode( '/' , [ $m , $c ] ) );
                    if( $cData != null ){
                        foreach ( $cData as $a ){
                            $resources[] = $this->encryptResourceUrl( implode( '/' , [ $m , $c , $a ] ) );
                        }
                    }
                }
            }
        }
        
        return $resources;
    }
    
    private function encryptResourceUrl( $url ){
        $url = trim( $url , '/' );
        return $url;
        //return md5( $url . 'iConsentCMS' );
    }
    
    public function actionPasswordReset(){
        $user = User::findOne( [ 'id' => 1 ] );
        
        $user->auth_key = \Yii::$app->security->generateRandomString( 32 );
        $user->password_hash = \Yii::$app->security->generatePasswordHash( 'Admin#123' );
        
        if( $user->save( [ 'password' , 'auth_key' ] ) ){
            $response =  [ "status" => 1 , "success" => [ "message" => "Password changed successfully" ] ];
        }else{
            $response =  [ "status" => 0 , "error" => $user->errors ];
        }
        
        return $response;
    }
    
    public function actionCheckVersion(){
        
        $response =  [ "status" => 0 , "error" => "Somthing Wrong!" ];
        
        $version = (new \yii\db\Query())
        ->select(['version_code','version_name','link'])->from('android_version')
        ->orderBy(['id' => SORT_DESC])->one();
        
        if( $version != null ){
            $response =  [ "status" => 1 , "data" => [ 'version_code' => $version['version_code'] , 'version_name' => $version['version_name'] , 'link' => $version['link'] ] ];
        }
        return $response;
    }
}
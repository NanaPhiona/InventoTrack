<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Models\Utils;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ApiController extends BaseController
{

    //Api function for uploading images
    public function image_uploads(Request $r){
        $path = Utils::file_upload($r->file('photo'));
        die($path);
        if($path == null){
            return "File not uploaded";
        }
        Utils::success([
            'file_name' => $path
        ], "Image uploaded successfully");
        // die("Time for uploading Images");
    }

    //Listing in API endpoints
    public function api_list(Request $r, $model){
        $u = Utils::get_user($r);
        if ($u == null){
            Utils::error("Unauthicated User");
        }

        $model = "App\Models\\" . $model;
        $data = $model::where('company_id', $u->company_id)->limit(10000)->get();
        Utils::success($data, "Listed successfully.");

    }

    public function manifest(Request $r){
        $u = Utils::get_user($r);
        if ($u == null){
            Utils::error("Unauthicated User");
        }
        $role = DB::table('admin_role_users')->where('user_id', $u->id)->get();
        $company = Company::find($u->company_id);
        $data =[
            'name' => 'Invento Tracker',
            'short-name' => 'IT',
            'description' => 'Inventory Tracking System',
            'version' => '1.0.0',
            'author' => 'Phiona Nankya',
            'company' => $company,
            'user' => $u,
            'role' => $role
        ];
        Utils::success($data, 'Success');

    }


    //Updating and creating API endpoints
    public function api_update(Request $r, $model){
       $u = Utils::get_user($r);
       if($u == null){
        Utils::error("Unauthenticated User");
       }

       $model = "App\Models\\". $model;
       $object = $model::find($r->id);
       $isEdit = true;
       if($object == null){
        $object = new $model();
        $isEdit = false;
       }


       $table_name = $object->getTable();
       $columns = Schema::getColumnListing($table_name);
       $except = ['id', 'created_at', 'updated_at'];
       $data = $r->all();

       foreach($data as $key => $value){
        if(!in_array($key, $columns)){
            continue;
        }
        if (in_array($key, $except)){
            continue;
        }

        $object->$key = $value;
       }
       $object->company_id = $u->company_id;

      
       if($r->temp_file_field != null){
            if(strlen($r->temp_file_field) > 1){
                $file = $r->file('photo');
                //If the file is not null, we set the path to an empty string
                if ($file != null){
                    $path = "";
                    try{
                        $path = Utils::file_upload($r->file('photo'));
                    } catch(\Exception $e){
                        $path = "";
                    }
                    if(strlen($path) > 3){
                        $field_name = $r->temp_file_field;
                        $object->$field_name = $path;
                    }
                }
            }
       }

      try{
        $object->save();
      }catch(\Exception $e) {
        Utils::error($e->getMessage());
      }

      $new_object = $model::find($object->id);

      if ($isEdit){
        Utils::success($new_object, "Updated successfully");
      } else{
        Utils::success($new_object, "Created Successful");
      }

    }

    //API for user registration
    public function register(Request $request){
       
        If($request->first_name == null){
            Utils::error("First name is required");
        }

        If($request->last_name == null){
            Utils::error("Last name is required");
        }

        If(!filter_var($request->email, FILTER_VALIDATE_EMAIL)){
            Utils::error("Email is invalid");
        }

        $u = User::where('email', $request->email)->first();
        if($u != null){
            Utils::error('Email is already registered.');
        }

        if ($request->password == null){
            Utils::error("Password is required.");
        }

        if($request->company_name == null){
            Utils::error("Company name is required.");
        }

        if ($request->currency == null){

        }

        $new_user = new User();
        $new_user->first_name = $request->first_name;
        $new_user->last_name = $request->last_name;
        $new_user->name = $request->first_name . " " . $request->last_name;
        $new_user->username = $request->username;
        $new_user->email = $request->email;
        $new_user->password = password_hash($request->password, PASSWORD_DEFAULT);
        $new_user->phone_number = $request->phone_number;
        $new_user->company_id = 1;
        $new_user->status = "Active";

        try{
            $new_user->save();
        } catch (\Exception $e){
            Utils::error($e->getMessage());
        }
        $new_user->save();

        $registered_user = User::find($new_user->id);
        if ($registered_user == null){
            Utils::error("Failed to register user.");
        }


        $company = new Company();
        $company->owner_id = $registered_user->id;
        $company->name = $request->company_name;
        $company->email = $request->email;
        $company->status = "Active";
        $company->license_expire = date('Y-m-d', strtotime("+1 year"));
        $company->currency = $request->currency;

        try{
            $company->save();
        } catch(\Exception $e){
            Utils::error($e->getMessage());
        }

        $registred_company = Company::find($company->id);
        if($registred_company == null){
            Utils::error("Failed to register company.");
        }


        //User roles
        DB::table('admin_role_users')->insert([
            'user_id' => $registered_user->id,
            'role_id' => 2,
        ]);

        Utils::success([
            'user' => $registered_user,
            'company' => $registred_company
        ], "Registration successful");
    }

    public function login(Request $request){
        //check if email is provided
        if ($request->email == null){
            Utils::error('Email is required.');
        }

        //check if email is valid
        if(!filter_var($request->email, FILTER_VALIDATE_EMAIL)){
            Utils::error("Email is invalid.");
        }

        //check if password is provided
        if ($request->password == null){
            Utils::error("Password is required");
        }
        //Checking if the provided email is in the system
        $user = User::where('email', $request->email)->first();
        if($user == null){
            Utils::error("Account not found");
        }

        if(!password_verify($request->password, $user->password)){
            Utils::error("Password does not match");       
        }

        $company = Company::find($user->company_id);
        if($company == null){
            Utils::error("Company not found.");
        }

        Utils::success([
            'user' => $user,
            'company' => $company
        ], "Login Successful.");
    }
}

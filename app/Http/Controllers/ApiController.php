<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Illuminate\Support\Facades\Storage;
// use Illuminate\Support\Facades\Auth as FacadesAuth;

class ApiController extends Controller
{
    public function get_home_products()
    {
        $products = Product::all();
        $part1 = [];
        $part2 = [];
        $part3 = [];
        foreach ($products as $product) {
            $product_array = [
                "name" => $product->name,
                "image" => $product->first_image(),
                "main_price" => $product->main_price(),
                "price" => $product->price,
                "discount" => $product->discount(),
            ];
            if ($product->type == "TYPE1") {
                $part1[] = $product_array;
            }
            if ($product->type == "TYPE2") {
                $part2[] = $product_array;
            }
            if ($product->type == "TYPE3") {
                $part3[] = $product_array;
            }
        }
        $products_parts = [
            "part1" => $part1,
            "part2" => $part2,
            "part3" => $part3
        ];

        return $products_parts;
        //     $products_array[] = [
        //         'id' => $product->id,
        //         'name' => $product->product_name,
        //         'main_price' => $product->main_price(),
        //         'sup_price' => $product->sup_price(),
        //         'type' => $product->product_type,
        //         'images' => $product_images_names,
        //     ];
        // }
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => ['required', Rule::unique('users')],
            'email' => ['required', 'email', Rule::unique('users')],
            'password' => ['required', 'confirmed'],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'validate_err' => $validator->messages(),
            ]);
        } else {
            $table = new User;
            $table->username = $request->username;
            $table->email = $request->email;
            $table->password =  Hash::make($request->password);;
            $table->save();
            $token = $table->createToken("myapptoken")->accessToken;
            return response()->json([
                'status' => 200,
                'message' => 'Your account has been created successfully',
                'username' => $request->username,
                'email' => $request->email,
                'token'  => $token
            ]);
        }
    }

    public function login(Request $request)
    {
        $credentials = $request->only(['username_or_email', 'password']);
        $validator = Validator::make($credentials, [
            'username_or_email' => ['required'],
            'password' => ['required'],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'validate_err' => $validator->messages(),
            ]);
        } else {
            $username_or_email = user::where('username', $credentials["username_or_email"])->orWhere('email', $credentials["username_or_email"])->first();
            if ($username_or_email == "") {
                return response()->json([
                    'validate_err' => ['username_or_email' => "There is no email or username such that"],
                ]);
            } else {
                if (user::where('username', $credentials["username_or_email"])->first() != "") {
                    $credentials = array(
                        'username' => $credentials["username_or_email"],
                        'password' => $credentials["password"],
                    );
                } else {
                    $credentials = [
                        'email' => $credentials["username_or_email"],
                        'password' => $credentials["password"],
                    ];
                }
            }
            // return $credentials;
            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'validate_err' => ['password' => "Password is wrong please try agine"],
                ]);
            } else {
                $user = Auth::user();
                $token = $user->createToken("myapptoken")->accessToken;
                return response()->json([
                    'status' => 200,
                    'message' => 'You have successfully logged in',
                    'username' => $user->username,
                    'email' => $user->email,
                    'token'  => $token
                ]);
            }
        }
    }


    public function update_user_profile(Request $request)
    {
        $validator = Validator::make(["file" => $request->file], [
            'file' => 'required|image|mimes:jpeg,png,jpg,gif|max:8048'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'validate_err' => $validator->messages(),
            ]);
        }
        $user = Auth::user();
        $base64 = $user->update_profile_image($request->file('file'));
        return response()->json([
            'status' => 200,
            'message' => 'Your profile has been updated successfully',
            'base64' => $base64,
        ]);
    }

    public function get_user_profile_photo()
    {
        $user = Auth::user();
        return response()->json([
            'base64' =>  $user->image(),
        ]);
    }

    public function get_products_list()
    {
        $products_array = [];
        $products = Product::all();
        // return  $products[0]->has_discount();
        foreach ($products as $product) {
            if ($product->has_discount()) {
                $price_after_discount = $product->main_price();
            } else {
                $price_after_discount = null;
            }


            array_push($products_array, [
                "id" => $product->id,
                "image" => $product->first_image(),
                "name" => $product->name,
                "price_before_discount" => $product->price,
                "price_after_discount" => $price_after_discount,
                "type" => $product->type,
            ]);
        }
        return $products_array;
    }

    public function add_new_product(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'price' => ['required', 'integer'],
            'type' => ['required'],
            'description' => ['required'],
            'delivery_possibility' => ['required'],
            'quantity' => ['required', 'integer'],
            'product_length' => ['required', 'integer'],
            'product_height' => ['required', 'integer'],
            'product_width' => ['required', 'integer'],
            'images' => 'array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:8048'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'validate_err' => $validator->messages(),
            ]);
        }
        $product_id = DB::table('products')->insertGetId(
            [
                'name' => $request->name,
                'price' => $request->price,
                'type' => $request->type,
                'delivery_possibility' => $request->delivery_possibility,
                'quantity' => $request->quantity,
                'length' => $request->product_length,
                'height' => $request->product_height,
                'width' => $request->product_width,
                'description' => $request->description,
            ]
        );

        if ($request->has('images')) {
            $i = 0;
            foreach ($request->file('images') as $image) {
                $path = Storage::putFile('public/images/products images', $image);
                DB::table('product_images')->insert(
                    [
                        'product_id' => $product_id,
                        'image_path' => $path,
                    ]
                );
            }
        }
        return response()->json([
            'status' => 200,
            'message' => 'Product has been Added successfully',
        ]);
    }


    public function get_product($name)
    {
        $product = Product::where('name', implode(' ', explode('_', $name)))->first();
        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'price' =>  $product->main_price(),
            'type' =>  $product->type,
            'description' =>  $product->description,
            'delivery_possibility' =>  $product->delivery_possibility,
            'quantity' =>  $product->quantity,
            'length' =>  $product->length,
            'height' =>  $product->height,
            'width' =>  $product->width,
            'images_preview' =>  $product->images(),
            "discount" => $product->discount(),

        ]);
        return $product;
    }

    public function update_product(Request $request, $id)
    {
        // return $request;
        $product = Product::where('id',  $id)->first();
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'price' => ['required', 'integer'],
            'type' => ['required'],
            'description' => ['required'],
            'delivery_possibility' => ['required'],
            'quantity' => ['required', 'integer'],
            'product_length' => ['required', 'integer'],
            'product_height' => ['required', 'integer'],
            'product_width' => ['required', 'integer'],
            'images' => 'array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:8048'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'validate_err' => $validator->messages(),
            ]);
        }
        if ($request->remove_list) {
            foreach ($request->remove_list as $id) {
                $product->remove_image($id);
            }
        }

        DB::table('products')->where('id', $id)->update(
            [
                'name' => $request->name,
                'price' => $request->price,
                'type' => $request->type,
                'delivery_possibility' => $request->delivery_possibility,
                'quantity' => $request->quantity,
                'length' => $request->product_length,
                'height' => $request->product_height,
                'width' => $request->product_width,
                'description' => $request->description,
            ]
        );
        if ($request->has('images')) {
            foreach ($request->file('images') as $image) {
                $path = Storage::putFile('public/images/products images', $image);
                DB::table('product_images')->insert(
                    [
                        'product_id' => $id,
                        'image_path' => $path,
                    ]
                );
            }
        }
        return response()->json([
            'status' => 200,
            'message' => 'Product has been Updated successfully',
        ]);
    }


    public function update_product_discount(Request $request, $id)
    {
        // return $request;
        $validator = Validator::make($request->all(), [
            'discount_value' => ['required'],
            'select_discount_type' => ['required'],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'validate_err' => $validator->messages(),
            ]);
        }
        $product = Product::where('id',  $id)->first();
        $product->update_discount($request->discount_value, $request->select_discount_type);
        return response()->json([
            'status' => 200,
            'message' => 'Product discount has been Updated successfully',
        ]);
    }


    public function authCheck()
    {
        return response()->json([
            'message' => 'login successfully',
        ]);
    }
    public function logout()
    {
        auth()->user()->tokens()->delete();
        return response()->json([
            'status' => 200,
            'message' => 'Logged out successfully',
        ]);
    }
}

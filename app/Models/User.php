<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Auth;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'username',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];



    public function profile()
    {
        return DB::table('profiles')->where('user_id', Auth::user()->id)->get()->first();
    }

    public function image()
    {
        $profile = DB::table('profiles')->where('user_id', Auth::user()->id)->get()->first();

        if ($this->profile() == null) {
            $base64 = base64_encode(Storage::get("public/images/profile images/default.jpg"));
        } else {
            $base64 = base64_encode(Storage::get($profile->image_path));
        }
        return $base64;
    }

    public function update_profile_image($image)
    {
        $user = Auth::user();
        $profile = DB::table('profiles')->where('user_id', Auth::user()->id)->get()->first();
        if ($user->profile() == null) {
            $InsertType = "insert";
            $path = Storage::putFile('public/images/profile images', $image);
        } else {
            Storage::delete($profile->image_path);
            $path = Storage::putFile('public/images/profile images', $image);
            $InsertType = "update";
        }
        DB::table('profiles')->where('id', $user->id)->$InsertType(
            [
                'user_id' => $user->id,
                'image_path' => $path
            ]
        );
        return  base64_encode(Storage::get($path));
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use function PHPUnit\Framework\isEmpty;

class Product extends Model
{


    public function discount_row()
    {
        return DB::table("discounts")->where("product_id", $this->id)->get()->first();
    }
    use HasFactory;
    protected $fillable = [
        'product_name',
        'product_price',
        'product_discunt',
        'product_price_after_discun',
        'product_price_save',
        'product_price_discunt_type',
        'product_type',
        'product_delivery_possibility',
        'product_quantity',
        'product_length',
        'product_height',
        'product_width',
        'product_description'
    ];

    public function remove_image($id)
    {
        $image = DB::table("product_images")->where("id", $id)->get()->first();
        Storage::delete($image->image_path);
        $image = DB::table("product_images")->where("id", $id)->delete();
    }

    public function images()
    {
        $images = DB::table("product_images")->where("product_id", $this->id)->get();
        $images_array = [];
        if ($images->first()) {
            // There is a matching row, return the value of the product_imge_name column
            foreach ($images as $image) {
                array_push($images_array, ["id" => $image->id, "base64" => base64_encode(Storage::get($image->image_path))]);
            }
        } else {
            // There are no matching rows, return "default_image.png
            array_push($images_array, ["id" => "no image", "base64" => base64_encode(Storage::get("public/images/products images/default_image.png"))]);
        }
        return $images_array;
    }
    public function discount()
    {
        if ($this->has_discount()) {
            return [
                "value" => $this->discount_row()->value, "type" => $this->discount_row()->type, "price_before_discount" => $this->price, "save" =>  $this->price - $this->main_price()
            ];
        } else {
            return null;
        }
    }
    // product info
    public function main_price()
    {
        if ($this->has_discount()) {
            // thare is discount
            if ($this->discount_row()->type == 'fixed') {
                // fixid
                $main_price = $this->price - $this->discount_row()->value;
            } else {
                // percentage
                $main_price = ($this->price * (100 - $this->discount_row()->value)) / 100;
            }
        } else {
            // no discount
            $main_price = $this->price;
        }
        return $main_price;
    }

    // public function sup_price()
    // {
    //     if (product_discunt::where('id', $this->id)->first() == "") {
    //         // no discunt
    //         $sub_price = "";
    //     } else {
    //         // there is discunt
    //         $sub_price = $this->product_price;
    //     }
    //     return $sub_price;
    // }

    // public function price_save()
    // {
    //     return $this->sup_price() - $this->main_price();
    // }

    // public function discount_value()
    // {
    //     return product_discount::where('id', $this->id)->first()->discunt_value;
    // }

    public function has_discount()
    {
        if (DB::table("discounts")->where("product_id", $this->id)->get()->first()) {
            return true;
        } else {
            return false;
        }
    }

    public function update_discount($value, $type)
    {

        // return  $this->has_discount();
        if ($this->has_discount()) {
            $InsertType = "update";
        } else {
            $InsertType = "insert";
        }
        DB::table('discounts')->where('product_id', $this->id)->$InsertType(
            [
                'product_id' => $this->id,
                'value' => $value,
                'type' => $type
            ]
        );
    }


    public function first_image()
    {
        $result = DB::table("product_images")->where("product_id", $this->id)->get()->first();
        if ($result) {
            // There is a matching row, return the value of the product_imge_name column
            $path = $result->image_path;
        } else {
            // There are no matching rows, return "default_image.png
            $path = "public/images/products images/default_image.png";
        }
        return base64_encode(Storage::get($path));
    }
}

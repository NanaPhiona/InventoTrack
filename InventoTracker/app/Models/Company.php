<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    //boot
    protected static function boot(){
        parent::boot();

        //creating (before)
        static::updated(function  ($company){
            $owner = User::find($company->owner_id);
            if ($owner == null){
                throw new \Exception('Owner not found');
            }
            $owner->company_id = $company->id;
            $owner->save();
        });

    }
}

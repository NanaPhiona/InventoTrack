<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Utils extends Model
{
    use HasFactory;

    static function getActiveFinancialPeriod($company_id){
        return FinancialPeriod::where('company_id', $company_id)
        ->where('status', 'Active')->first();
    }

    //function for generating SKU Automatically
    static public function generateSKU($sub_category_id){
        $year = date('Y');
        $sub_category = StockSubCategory::find($sub_category_id);
        $serial = StockItem::where('stock_sub_category_id', $sub_category_id)->count() + 1;
        $sku = $year . "-" . $sub_category->id. "-" . $serial;
        return $sku;
    }
}

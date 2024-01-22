<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockSubCategory extends Model
{
    use HasFactory;


    public function update_self(){
        $active_financial_period = Utils::getActiveFinancialPeriod($this->company_id);
        if($active_financial_period == null){
            return;
        }
        //declaring the total buying price and selling price to 0
        $total_buying_price = 0;
        $total_selling_price = 0;
        $current_quantity = 0;
        //finding stock items that are in a certain stock category and are in an active financial period
        $stock_items = StockItem::where('stock_sub_category_id', $this->id)
            ->where('financial_period_id', $active_financial_period->id)
            ->get();
        //looping through the stock_items to get the total investment and total sales in a particular stock category.
        foreach($stock_items as $key => $value){
            $total_buying_price += ($value->buying_price *  $value->original_quantity);
            $total_selling_price += ($value->selling_price * $value->original_quantity);
            $current_quantity += $value->current_quantity;

        }
        $total_expected_profit = $total_selling_price - $total_buying_price;
        //dd($total_buying_price);

        //earned_profit
        $this->earned_profit = StockRecord::where('stock_sub_category_id', $this->id)
            ->where('financial_period_id', $active_financial_period->id)
            ->sum('profit');
       
        $this->buying_price = $total_buying_price;
        $this->selling_price = $total_selling_price;
        $this->expected_profit = $total_expected_profit;
        $this->current_quantity = $current_quantity;
        

        if($current_quantity > $this->reorder_level){
            $this->in_stock = 'Yes';
        }else{
            $this->in_stock = 'No';
        }
        $this->save();

    }
    
    
    
    
    
    public function stockCategory()
    {
        return $this->belongsTo(stockCategory::class);
    }

    protected $appends = ['name_text'];

    //getter for name_text
    public function getNameTextAttribute()
    {
        $name_text = $this->name;
        if ($this->stockCategory != null){
            $name_text = $name_text . " - " . $this->stockCategory->name;
        }
        return $name_text;
    }
}

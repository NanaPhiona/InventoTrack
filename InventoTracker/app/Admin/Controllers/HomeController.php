<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\StockRecord;
use App\Models\User;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;

class HomeController extends Controller
{
    public function index(Content $content)
    {
        $u = Admin::user();
        $company = Company::find($u->company_id);

        return $content
            ->title($company->name)
            ->description(' - '.$u->name)
            // ->row(Dashboard::title())
            ->row(function (Row $row){
                $row->column(3, function (Column $column){
                    $count = User::where('company_id', Admin::user()->company_id)->count();
                    $box = new Box('Employees','<h3 style="text-align:center; margin:0; font-size:40px; font-weight: bold;">' . $count .'</h3>');
                    $box->style('success')
                        ->solid();
                    $column->append($box);

                });

                $row->column(3, function(Column $column){
                    $total_sales = StockRecord::where('company_id', Admin::user()->company_id)
                        ->sum('total_sales');
                    $u = Admin::user();
                    $company = Company::find($u->company_id);
                    $box = new Box('Total Sales','<h3 style="text-align:center; margin:0; font-size:40px; font-weight: bold;">' .$company->currency . " " .number_format($total_sales) .'</h3>');
                    $box->style('success')
                        ->solid();
                    $column->append($box);
                });
            });
    }
}

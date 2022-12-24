
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\VoucherController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//Voucher
Route::get('/gen_voucher', [VoucherController::class, 'generateVoucher']);
Route::get('/get_voucher', [VoucherController::class, 'getVoucherCode']);
Route::get('/claim_voucher/{voucher_code}', [VoucherController::class, 'claimVoucher']);
Route::get('/voucher_stats', [VoucherController::class, 'voucherStats']);
//End Voucher

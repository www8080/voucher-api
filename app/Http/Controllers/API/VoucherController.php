<?php
namespace App\Http\Controllers\API;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Controllers\API\BaseController;
use App\Models\Voucher;

ini_set('memory_limit', '4000M');
ini_set('output_buffering', '8092M');

class VoucherController extends BaseController
{
    /**
     * Get 1 Active Voucher Code
     *
     * @return \Illuminate\Http\Response
     */
    public function getVoucherCode()
    {
	return Voucher::select('voucher_code')->where('status', 'Available')->whereDate('expiry_date', '>=', Carbon::now())->limit(1)->get();
    }

   /**
     * Claim Voucher Code
     *
     * @return \Illuminate\Http\Response
     */
    public function claimVoucher(Request $request, $voucherCode)
    {
	try {
	     $chkVoucher = Voucher::where('voucher_code', $voucherCode)->where('status', 'Available')->where('expiry_date', '>=', Carbon::now())->get();
		//dd(count($chkVoucher));
		if (count($chkVoucher) === 1) {
		   Voucher::where('voucher_code', $voucherCode)->update(['status' => 'Claimed', 'expiry_date' => Carbon::now()]);
		   $msg = 'Voucher Code:'. $voucherCode .' Claimed';
		   } else {
		      $msg = 'Voucher Code: '. $voucherCode .' was invalid/claimed';
		   }
	   return $this->sendResponse($voucherCode, $msg);
	} catch (ModelNotFoundException $ex) {
	    return $this->sendError('Error');
	  } catch (Exception $ex) {
	   return $this->sendFailureDefaultResponse();
	  }
    }

    /**
     * Get 1 Active Voucher Code
     *
     * @return \Illuminate\Http\Response
     */
    public function voucherStats()
    {
	$voucher_available   = Voucher::where('status', 'Available')->count();
	$voucher_claimed     = Voucher::where('status', 'Claimed')->count();
	$voucher_expired     = Voucher::where('status', 'Expired')->count();
	$voucher_expiring_6  = Voucher::where('expiry_date', '<=' , Carbon::now()->addHours(6)->toDateTimeString())->where('status','!=','Expired')->count();
	$voucher_expiring_12 = Voucher::where('expiry_date', '<=' , Carbon::now()->addHours(12)->toDateTimeString())->where('status','!=','Expired')->count();
	$voucher_expiring_24 = Voucher::where('expiry_date', '<=' , Carbon::now()->addHours(24)->toDateTimeString())->where('status','!=','Expired')->count();
	//$voucher_expiring_6 = Voucher::whereRaw("DATE_FORMAT(expiry_date, 'Y-m-d H:i')->diffInHours(Carbon::now()->format('Y-m-d H:i')) > 360")->count();
	//$voucher_expiring_6 = Voucher::where('expiry_date', '>' , Carbon::now())->count();
	//dd(DB::getQueryLog());

	$data['stats']['available']  = $voucher_available;
	$data['stats']['claimed']    = $voucher_claimed;
	$data['stats']['expired']    = $voucher_expired;
	$data['expiring']['hour_6']  = $voucher_expiring_6;
	$data['expiring']['hour_12'] = $voucher_expiring_12;
	$data['expiring']['hour_24'] = $voucher_expiring_24;

	$msg = "Voucher Stats Result";

	return $this->sendResponse($data, $msg);
    }


    /**
     * Generate Voucher Code
     *
     * @return \Illuminate\Http\Response
     */
    public function generateVoucher()
    {
	$timeStart        = microtime(true);
	//$totalCode        = 1090000;
	$totalCode        = 100;
	$processTask      = 2;
	$totalEachFile    = $totalCode / $processTask;
	$dataFromPos      = 0;

    	function getRandomCode($length) {
           //$str = 'abcdefghijklmnopqrstuvwxyz';
 	   $str1= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
           $str2= '0123456789';
	   $shuffled1 = substr(str_shuffle($str1), 0, $length/2);
	   $shuffled2 = substr(str_shuffle($str2), 0, $length/2);
	   $result = $shuffled1.$shuffled2;

	   return $result;
       }

	$codeArray = array();

	for ($i=0;$i<$totalCode;$i++) {
           //$codeArray[] = getRandomCode(6);
	   //$codeArray['data'][$i] = array('voucher_code'=> getRandomCode(6),'status'=> "Available",'expiry_date'=> "2022-12-23",'created_at'=> Carbon::now()->toDateTimeString(),'updated_at'=> Carbon::now()->toDateTimeString());
	   //$expiryDate    = date('Y-m-d', strtotime($this->getExpiryDate()));
	   $expiryDatePeriod = CarbonPeriod::between(Carbon::today()->subDay(3), Carbon::today()->addDay(3));
	   $expiryDatePerod  = $expiryDatePeriod->toArray();
	   $expiryDate	     = $expiryDatePerod[array_rand($expiryDatePerod)]->format("Y-m-d");
	   //dd(print_r($expiryDatePerod[array_rand($expiryDatePerod)],true));
	   //dd($expiryDatePerod[array_rand($expiryDatePerod)]->format("Y-m-d"));
	   $voucherStatus = (Carbon::parse($expiryDate) > Carbon::now()->format('Y-m-d')) ? 'Available' : 'Expired';
	   //echo "$expiryDate:$voucherStatus ***"; 

	   $codeArray['data'][$i] = array('voucher_code'=> getRandomCode(6),'status'=> $voucherStatus,'expiry_date'=> $expiryDate);
    	}
	//var_dump($codeArray);
    	$uniqueCode = array_values(array_unique($codeArray));
	$myfile     = file_put_contents('/var/www/html/sugarbook/storage/tmp/voucher_code.txt', print_r($uniqueCode[0], true), LOCK_EX);
	//var_dump($uniqueCode);
	//print_r($uniqueCode);

	for ($i=0; $i<$processTask; $i++) {
	   //echo("From: " . $dataFromPos . " - Each File Total:" . $totalEachFile);

           $arrayIns     = array_slice($uniqueCode[0], $dataFromPos, $totalEachFile);
	   //$chunks = array_chunk($test4, $totalEachFile);
	   //foreach($chunks as $key => $value) {
              file_put_contents("/var/www/html/sugarbook/storage/tmp/voucher_code_".$i.".txt", json_encode($arrayIns) , LOCK_EX);
	   //}
           $dataFromPos += $totalEachFile;

           //dd("Total file$i: " . number_format(sizeof($arrayIns)));

           //echo("Total File UniqueCode$i: " . number_format(sizeof($arrayIns)).'<br/>');
        } 
	//$test0 = json_encode($arrayIns[0]);
	//$test1 = json_encode($uniqueCode[0]);
	//$test2 = json_decode($test1,true);
	//$chunks = $test1->chunk(50);
	//$chunks = array_chunk($test2, 5);

	//sleep(5);
	//$processTask;

	$x = 0;
	for ($i=0; $i<$processTask; $i++) {
	   $test3 = file_get_contents("/var/www/html/sugarbook/storage/tmp/voucher_code_".$i.".txt");
	   $test4 = json_decode($test3,true);
	   $chunks = array_chunk($test4, 10000);
	   //$test3  = json_encode($chunks[0]);
	   //dd($test4);
	   //var_dump($test4);
 	  foreach($chunks as $key => $value) {
	     //var_dump($value). "********";
	     //$x++;
	     DB::table('vouchers')->upsert($value,'voucher_code'); 
   	     //sleep(5);
	     //echo "$x**-";
	     /*
	     if($x == 5) {
		break;
	     }*/
	  }
	}
/*
        $test3 = file_get_contents("/var/www/html/sugarbook/storage/tmp/voucher_code_1".".txt");
        $test4 = json_decode($test3,true);
        $chunks = array_chunk($test4, $totalEachFile);
        //$test3  = json_encode($chunks[0]);
        //dd($test4);
        //var_dump($test4);
        foreach($chunks as $key => $value) {
          //var_dump($value). "********";
          DB::table('vouchers')->upsert($value,'voucher_code');
          sleep(3);
        }
*/
	


	//DB::table('vouchers')->upsert(json_decode($test3,true),'voucher_code');
	/*
	$expiryDate = date('Y-m-d', strtotime($this->getExpiryDate()));
	if(Carbon::parse($expiryDate) < Carbon::now()->format('Y-m-d')){
   	   $voucherStatus  = "Expired";
	} else {
	   $voucherStatus = "Available";
	}
	echo $expiryDate ."****" . $voucherStatus;

    	//echo "Total CodeArray: " . number_format(sizeof($codeArray[0])).'<br/>'; 
	echo "Total UniqueCode: " . number_format(sizeof($uniqueCode[0])).'<br/>';
	*/

	// Display Script End time
	
	//Insert DB
	//DB::table('vouchers')->insert(
	//			['voucher_code' => getRandomCode(6), 'status' => $voucherStatus, 'expiry_date' => Carbon::now(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]
	//		);

	//End Insert DB

	$timeEnd = microtime(true);
        //dividing with 60 will give the execution time in minutes other wise seconds
        $executionTime = ($timeEnd - $timeStart)/60;

        //execution time of the script
        //echo '<br /><b>Total Execution Time:</b> '.number_format($executionTime,2).' Mins';

	return response()->json(['Status'=>'Success','Voucher_Generated'=>number_format(sizeof($uniqueCode[0])),'Total_Processing_Time'=>number_format($executionTime,2).' Mins'], 200);
    }

    public function getExpiryDate(){
        //Get Last 3 days
	$expiryDate = array();
        $start = Carbon::now()->subDays(4);
        for ($i = 0; $i <= 3; $i++) {
          $start->addDays(1);
          //print $start->format('Y-m-d') ."<br />";
          $expiryDate[] = $start->format('Y-m-d');
        }
        //Get Future 3 days
        $start = Carbon::now();
        for ($i = 0; $i < 3; $i++) {
          $start->addDays(1);
          //print $start->format('Y-m-d') . '<br/>';
          $expiryDate[] = $start->format('Y-m-d');
        }
        //var_dump($expiryDate);
        $random  = function($arr) {return $arr[array_rand($arr)];};
        $expiryDate = $random($expiryDate);

        return $expiryDate;
    }    

}

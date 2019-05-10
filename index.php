<?php
include_once ('./environment.php');

$minute = date('i');
if($minute>=0 && $minute<15){
	$start_date =  date('Y-m-d H:i:s', mktime(date('H')-1,45,0,date('m'),date('d'),date('Y')));
	$end_date =  date('Y-m-d H:i:s', mktime(date('H')-1,59,59,date('m'),date('d'),date('Y')));
}elseif($minute>=15 && $minute<30){
	$start_date =  date('Y-m-d H:i:s', mktime(date('H'),0,0,date('m'),date('d'),date('Y')));
	$end_date =  date('Y-m-d H:i:s', mktime(date('H'),14,59,date('m'),date('d'),date('Y')));
}elseif($minute>=30 && $minute<45){
	$start_date =  date('Y-m-d H:i:s', mktime(date('H'),15,0,date('m'),date('d'),date('Y')));
	$end_date =  date('Y-m-d H:i:s', mktime(date('H'),29,59,date('m'),date('d'),date('Y')));
}else{
	$start_date =  date('Y-m-d H:i:s', mktime(date('H'),30,0,date('m'),date('d'),date('Y')));
	$end_date =  date('Y-m-d H:i:s', mktime(date('H'),44,59,date('m'),date('d'),date('Y')));
}



$db_sf = new msSqlConnection('db_ms_nasf');
$db_erp = new msSqlConnection('db_ms_erp');
$db_sftr = new msSqlConnection('db_ms_sftr');


$data = get_SF_TRANS_data($db_sf,$start_date,$end_date);
if($data){
	$keys = $data['transaction_keys'];
	
	$sf_trans_data = $data['summary_data'];
	$c_sftr_data = get_C_SFTR_data($db_erp,$keys);
	$c_sfetr_data = get_C_SFETR_data($db_erp,$keys);
	$h_sftr_data = get_H_SFTR_data($db_erp,$keys);
	$itrn_his_data = get_ITRN_HIS_data($db_erp,$keys);
	$item_det_data = get_ITEM_DET_data($db_erp,array_keys($sf_trans_data));
	
	foreach($sf_trans_data as $key => $sf_trans_details){
			$c_sftr_details = $c_sftr_data[$key];
			$c_sfetr_details = $c_sfetr_data[$key];
			$h_sftr_details = $h_sftr_data[$key];
			$itrn_his_details = $itrn_his_data[$key];
			$item_det_details = $item_det_data[$key];
			
			$summary_array[$key] = arrayMerge(array($sf_trans_details,$c_sftr_details,$c_sfetr_details,$h_sftr_details,$itrn_his_details,$item_det_details));			
			ksort($summary_array);
	}
	
	record_data($db_sftr, $summary_array,$start_date,$end_date);
	display_data($summary_array);
}


function get_SF_TRANS_data($db,$start_date,$end_date){
	$result = $db -> fetch_data(
		"SELECT
			SF_TRANS.*,
			SF_ITEM.DESCRIPTION
		FROM SF_TRANS 
		LEFT JOIN SF_ITEM ON
			SF_ITEM.ITEM = SF_TRANS.ITEM
		WHERE
			SF_TRANS.TRANS_DATE>='$start_date'
			AND SF_TRANS.TRANS_DATE<'$end_date'
			AND SF_TRANS.POSTED=2
			AND SF_TRANS.TRANS_TYPE = 'WCOM'","set");
	$data = $result['data'];	
	
	if($data){
		$transaction_keys = array();
		foreach($data as $row => $details){
			$key = $details['item'];
			$transaction_keys[] = $details['pri_key'];
			$summary_data[$key]['description'] = $details['description'];
			$summary_data[$key]['sf_trans_total_quantity'] += $details['quantity'];
			$summary_data[$key]['sf_trans_transaction_count'] ++;
		}
	}
	
	return array('transaction_keys' => $transaction_keys,'summary_data'=>$summary_data);
}

function get_C_SFTR_data($db,$keys){
	$key_string = "'".implode("','",$keys)."'";
	$result = $db -> fetch_data(
		"SELECT
			*
		FROM C_SFTR
		WHERE
			LTRIM(RTRIM(C_TRANSACTION_NUMBER)) IN ($key_string)","set");
	$data = $result['data'];	
	
	if($data){
		foreach($data as $row => $details){
			$key = $details['item'];
			$summary_data[$key]['c_sftr_total_quantity'] += $details['qty'];
			$summary_data[$key]['c_sftr_transaction_count'] ++;
		}
	}
	
	return $summary_data;
}
function get_C_SFETR_data($db,$keys){
	$key_string = "'".implode("','",$keys)."'";
	$result = $db -> fetch_data(
		"SELECT
			*
		FROM C_SFETR 
		WHERE
			LTRIM(RTRIM(C_TRANSACTION_NUMBER)) IN ($key_string)","set");
	$data = $result['data'];	
	
	if($data){
		foreach($data as $row => $details){
			$key = $details['item'];
			$summary_data[$key]['c_sfetr_total_quantity'] += $details['qty'];
			$summary_data[$key]['c_sfetr_transaction_count'] ++;
		}
	}
	
	return $summary_data;
}
function get_H_SFTR_data($db,$keys){
	$key_string = "'".implode("','",$keys)."'";
	$result = $db -> fetch_data(
		"SELECT
			*
		FROM H_SFTR 
		WHERE
			LTRIM(RTRIM(C_TRANSACTION_NUMBER)) IN ($key_string)","set");
	$data = $result['data'];	
	
	if($data){
		foreach($data as $row => $details){
			$key = $details['item'];
			$summary_data[$key]['h_sftr_total_quantity'] += $details['qty'];
			$summary_data[$key]['h_sftr_transaction_count'] ++;
		}
	}
	
	return $summary_data;
}

function get_ITRN_HIS_data($db,$keys){
	$key_string = "'".implode("','",$keys)."'";

	$result = $db -> fetch_data(
		"SELECT
			*
		FROM ITRN_HIS 
		WHERE
			LTRIM(RTRIM(USER_ALPHA2)) IN ($key_string)","set");
	$data = $result['data'];	

	if($data){
		foreach($data as $row => $details){
			$key = $details['item'];
			$summary_data[$key]['itrn_his_total_quantity'] += $details['qty'];
			$summary_data[$key]['itrn_his_transaction_count'] ++;
		}
	}

	return $summary_data;
}

function get_ITEM_DET_data($db,$keys){
	$key_string = "'".implode("','",$keys)."'";
	$result = $db -> fetch_data(
		"SELECT
			*
		FROM ITEM_DET 
		WHERE
			ITEM IN ($key_string)","set");
	$data = $result['data'];	
	
	if($data){
		foreach($data as $row => $details){
			$key = $details['item'];
			$summary_data[$key]['item_det_total_quantity'] += $details['oh_qty'];
		}
	}
	
	return $summary_data;
}

function record_data($db, $summary_array,$start_date,$end_date){
	if($summary_array){
		$run_id = uniqid();
		
		foreach($summary_array as $item => $details){
			$description = $details['description'];
			
			$sf_trans_qty = intval($details['sf_trans_total_quantity']);
			$c_sftr_qty = intval($details['c_sftr_total_quantity']);
			$c_sfetr_qty = intval($details['c_sfetr_total_quantity']);
			$h_sftr_qty = intval($details['h_sftr_total_quantity']);
			$itrn_his_qty = intval($details['itrn_his_total_quantity']);
			$item_det_qty = intval($details['item_det_total_quantity']);
			
			$sf_trans_count = intval($details['sf_trans_transaction_count']);
			$c_sftr_count = intval($details['c_sftr_transaction_count']);
			$c_sfetr_count = intval($details['c_sfetr_transaction_count']);
			$h_sftr_count = intval($details['h_sftr_transaction_count']);
			$itrn_his_count = intval($details['itrn_his_transaction_count']);
			
			$result = $db -> modify_data(
				"INSERT INTO wcom_log (
					run_id,
					start_date,
					end_date,
					item,
					description,
					sf_trans_count,
					c_sftr_count,
					c_sfetr_count,
					h_sftr_count,
					itrn_his_count,
					sf_trans_qty,
					c_sftr_qty,
					c_sfetr_qty,
					h_sftr_qty,
					itrn_his_qty,
					item_det_qty
				) VALUES (
					'$run_id',
					'$start_date',
					'$end_date',
					'$item',
					'$description',
					$sf_trans_count,
					$c_sftr_count,
					$c_sfetr_count,
					$h_sftr_count,
					$itrn_his_count,
					$sf_trans_qty,
					$c_sftr_qty,
					$c_sfetr_qty,
					$h_sftr_qty,
					$itrn_his_qty,
					$item_det_qty
				)");
			$data = $result['data'];
		}
	}
	/*$result = $db -> modify_data(
				"INSERT INTO wcom_log (
					*
				FROM ITEM_DET 
				WHERE
					ITEM IN ($key_string)","set");
			$data = $result['data'];	*/
}

function display_data($summary_array){
	?>
  <table border="1" cellspacing="0" cellpadding="0" style="table-layout:fixed; width:100%">
    <tr>
        <td>Item</td>
        <td align="center">SF_TRANS Count</td>
        <td align="center">C_SFTR Count</td>
        <td align="center">C_SFETR Count</td>
        <td align="center">H_SFTR Count</td>
        <td align="center">ITM_HIS Count</td>
        <td width="10">&nbsp;</td>
        <td align="center">SF_TRANS Total</td>
       	<td align="center">C_SFTR Total</td>
        <td align="center">C_SFETR Total</td>
        <td align="center">H_SFTR Total</td>
        <td align="center">ITM_HIS Total</td>
        <td width="10">&nbsp;</td>
        <td align="center">ITEM_DET Qty</td>
      </tr>
  <?
	
	if($summary_array){
		foreach($summary_array as $key => $details){
			$sf_trans_qty = intval($details['sf_trans_total_quantity']);
			$c_sftr_qty = intval($details['c_sftr_total_quantity']);
			$c_sfetr_qty = intval($details['c_sfetr_total_quantity']);
			$h_sftr_qty = intval($details['h_sftr_total_quantity']);
			$itrn_his_qty = intval($details['itrn_his_total_quantity']);
			$item_det_qty = intval($details['item_det_total_quantity']);
			
			$sf_trans_count = intval($details['sf_trans_transaction_count']);
			$c_sftr_count = intval($details['c_sftr_transaction_count']);
			$c_sfetr_count = intval($details['c_sfetr_transaction_count']);
			$h_sftr_count = intval($details['h_sftr_transaction_count']);
			$itrn_his_count = intval($details['itrn_his_transaction_count']);

			if($sf_trans_qty==$itrn_his_qty){
				$bg_qty = '#A9D08E';
			}elseif($sf_trans_qty!==$itrn_his_qty){
				if($sf_trans_qty==($itrn_his_qty+$c_sfetr_qty)){
					$bg_qty = '#FFD966';
				}else{
					$bg_qty = '#F4B084';
				}
			}
			
			if($sf_trans_count==$itrn_his_count){
				$bg_count = '#A9D08E';			
			}else{
				if($sf_trans_count==($itrn_his_count+$c_sfetr_count)){
					$bg_qty = '#FFD966';
				}else{
					$bg_qty = '#F4B084';
				}
			}
			
			?>
			<tr>
				<td><?=$key?></td>
				<td align="center" style="background-color:<?=$bg_count?>"><?=$sf_trans_count?></td>
				<td align="center" style="background-color:<?=$bg_count?>"><?=$c_sftr_count?></td>
				<td align="center" style="background-color:<?=$bg_count?>"><?=$c_sfetr_count?></td>
        <td align="center" style="background-color:<?=$bg_count?>"><?=$h_sftr_count?></td>
				<td align="center" style="background-color:<?=$bg_count?>"><?=$itrn_his_count?></td>
				<td>&nbsp;</td>
				<td align="center" style="background-color:<?=$bg_qty?>"><?=$sf_trans_qty?></td>
				<td align="center" style="background-color:<?=$bg_qty?>"><?=$c_sftr_qty?></td>
				<td align="center" style="background-color:<?=$bg_qty?>"><?=$c_sfetr_qty?></td>
        <td align="center" style="background-color:<?=$bg_qty?>"><?=$h_sftr_qty?></td>
				<td align="center" style="background-color:<?=$bg_qty?>"><?=$itrn_his_qty?></td>
        <td>&nbsp;</td>
        <td align="center"><?=$item_det_qty?></td>
			</tr>
			<?
		}
	}
	
	?></table><?
}
?>
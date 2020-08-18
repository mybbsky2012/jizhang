<?php function allow_domain() {
	$is_allow=false;
	$servername=trim($_SERVER['SERVER_NAME']);
	$Array=array("localhost","127.0.0.1","test.com","test1.com");
	foreach($Array as $value) {
		$value=trim($value);
		$domain=explode($value,$servername);
		if(count($domain)>1) {
			$is_allow=true;
			break;
		}
	}
	if(!$is_allow) {
		die("本系统仅限本地使用！需要域名授权请联系邮件：mybbsky#qq.com");
	}
}
allow_domain();
define("SiteNameTitle",SiteName." - 自用版");
function state_day($start,$end,$uid,$type=0,$classid=0) {
	global $conn;
	if($start=="") {
		$start=date("Y-m-d");
	}
	if($end=="") {
		$end=date("Y-m-d");
	}
	$where = "where userid='$uid'";
	$where .= " and actime >=".strtotime($start." 00:00:00")." and actime <=".strtotime($end." 23:59:59");
	if($type!=0) {
		$where .= " and zhifu='$type' ";
	}
	if($classid!=0) {
		$where .= " and acclassid='$classid' ";
	}
	$sql = "SELECT sum(acmoney) as total FROM ".TABLE."account ".$where;
	$query = mysqli_query($conn,$sql);
	$row = mysqli_fetch_array($query);
	if($row['total']) {
		$money = $row['total'];
	} else {
		$money = "0.00";
	}
	echo $money;
}
function total_count($classid=0,$year,$uid,$zhifu=0) {
	global $conn;
	$where = "where FROM_UNIXTIME(actime,'%Y')='$year' and userid='$uid'";
	if($classid!=0) {
		$where .= " and acclassid='$classid' ";
	}
	if($zhifu!=0) {
		$where .= " and zhifu='$zhifu' ";
	}
	$sql = "SELECT FROM_UNIXTIME(actime, '%m') AS month,sum(acmoney) AS total FROM ".TABLE."account ".$where." GROUP BY month";
	$query = mysqli_query($conn,$sql);
	$resArr = array();
	while($row = mysqli_fetch_array($query)) {
		$resArr[] = $row;
	}
	return $resArr;
}
function program_total_count($proid=0,$zhifu,$uid) {
	global $conn;
	$where = "where userid='$uid' and zhifu='$zhifu'";
	if($proid!=0) {
		$where .= " and proid='$proid' ";
	}
	$sql = "SELECT proid,sum(acmoney) AS total FROM ".TABLE."account ".$where." GROUP BY proid";
	$query = mysqli_query($conn,$sql);
	$resArr = array();
	while($row = mysqli_fetch_array($query)) {
		$resArr[] = $row;
	}
	return $resArr;
}
function user_first_year($uid) {
	global $conn;
	global $this_year;
	$sql = "SELECT actime FROM ".TABLE."account where userid='$uid' order by actime limit 1";
	$query = mysqli_query($conn,$sql);
	$row = mysqli_fetch_array($query);
	if($row['actime']) {
		$user_first_year = date("Y",$row['actime']);
	} else {
		$user_first_year = $this_year;
	}
	return $user_first_year;
}
function show_type($classtype,$uid) {
	global $conn;
	if($classtype<>"") {
		$sql = "select * from ".TABLE."account_class where userid='$uid' and classtype='$classtype' order by classtype asc,classid asc";
	} else {
		$sql = "select * from ".TABLE."account_class where userid='$uid' order by classtype asc,classid asc";
	}
	$query = mysqli_query($conn,$sql);
	$resArr = array();
	while($row = mysqli_fetch_array($query)) {
		$resArr[] = $row;
	}
	return $resArr;
}
function show_program($uid) {
	global $conn;
	$sql = "select * from ".TABLE."program where userid='$uid' order by orderid desc,proid desc";
	$query = mysqli_query($conn,$sql);
	$resArr = array();
	while($row = mysqli_fetch_array($query)) {
		$resArr[] = $row;
	}
	return $resArr;
}
function itlu_page_search($uid,$pagesize=20,$page=1,$classid,$starttime="",$endtime="",$startmoney="",$endmoney="",$proid="",$bankid="",$select_sys_all="") {
	global $conn;
	if($select_sys_all=="") {
		$nums = record_num_query($uid,$classid,$starttime,$endtime,$startmoney,$endmoney,$proid,$bankid);
	} else {
		$nums = record_num_query($uid,$classid,$starttime,$endtime,$startmoney,$endmoney,$proid,$bankid,1);
	}
	$pages=ceil($nums/$pagesize);
	if($pages<1) {
		$pages=1;
	}
	if($page > $pages) {
		$page=$pages;
	}
	if($page < 1) {
		$page=1;
	}
	$kaishi=($page-1)*$pagesize;
	$sql = "SELECT a.*,b.classname FROM ".TABLE."account as a INNER JOIN ".TABLE."account_class as b ON b.classid=a.acclassid ";
	if($classid == "all") {
	} elseif($classid == "pay") {
		$sql .= " and zhifu = 2 ";
	} elseif($classid == "income") {
		$sql .= " and zhifu = 1 ";
	} else {
		$sql .= " and acclassid = '".$classid."' ";
	}
	if(!empty($bankid)) {
		$sql .= " and bankid = '".$bankid."' ";
	}
	if(!empty($starttime)) {
		$sql .= " and actime >= '".strtotime($starttime." 00:00:00")."' ";
	}
	if(!empty($endtime)) {
		$sql .= " and actime <= '".strtotime($endtime." 23:59:59")."' ";
	}
	if(!empty($startmoney)) {
		$sql .= " and acmoney >= '".$startmoney."' ";
	}
	if(!empty($endmoney)) {
		$sql .= " and acmoney <= '".$endmoney."' ";
	}
	if(!empty($proid)) {
		$sql .= " and proid = '".$proid."' ";
	}
	if($select_sys_all=="") {
		$sql .= "where a.userid = '$uid' and ";
		$sql .= "a.acid in (select acid from ".TABLE."account where userid = '$uid') order by a.actime desc,a.acid desc limit $kaishi,$pagesize";
	} else {
		$sql .= "where ";
		$sql .= "a.acid in (select acid from ".TABLE."account) order by a.actime desc,a.acid desc limit $kaishi,$pagesize";
	}
	$query = mysqli_query($conn,$sql);
	$resArr = array();
	while($row = mysqli_fetch_array($query)) {
		$resArr[] = $row;
	}
	return $resArr;
}
function itlu_page_query($uid,$pagesize=20,$page=1) {
	global $conn;
	$nums = record_num_query($uid,"all");
	$pages=ceil($nums/$pagesize);
	if($pages<1) {
		$pages=1;
	}
	if($page > $pages) {
		$page=$pages;
	}
	if($page < 1) {
		$page=1;
	}
	$kaishi=($page-1)*$pagesize;
	$sql = "SELECT a.*,b.classname FROM ".TABLE."account as a INNER JOIN ".TABLE."account_class as b ON b.classid=a.acclassid ";
	$sql .= "where a.userid = '$uid' and ";
	$sql .= "a.acid in (select acid from ".TABLE."account where userid = '$uid') order by a.actime desc limit $kaishi,$pagesize";
	$query = mysqli_query($conn,$sql);
	$resArr = array();
	while($row = mysqli_fetch_array($query)) {
		$resArr[] = $row;
	}
	return $resArr;
}
function record_num_query($uid,$classid="",$starttime="",$endtime="",$startmoney="",$endmoney="",$proid="",$bankid="",$select_sys_all="") {
	global $conn;
	if($select_sys_all=="") {
		$sql = "select count(acid) as total from ".TABLE."account where userid = '$uid'";
	} else {
		$sql = "select count(acid) as total from ".TABLE."account where 1 = 1";
	}
	if($classid == "all") {
	} elseif($classid == "pay") {
		$sql .= " and zhifu = 2 ";
	} elseif($classid == "income") {
		$sql .= " and zhifu = 1 ";
	} else {
		$sql .= " and acclassid = '".$classid."' ";
	}
	if(!empty($bankid)) {
		$sql .= " and bankid = '".$bankid."' ";
	}
	if(!empty($starttime)) {
		$sql .= " and actime >= '".strtotime($starttime." 00:00:00")."' ";
	}
	if(!empty($endtime)) {
		$sql .= " and actime <= '".strtotime($endtime." 23:59:59")."' ";
	}
	if(!empty($startmoney)) {
		$sql .= " and acmoney >= '".$startmoney."' ";
	}
	if(!empty($endmoney)) {
		$sql .= " and acmoney <= '".$endmoney."' ";
	}
	if(!empty($proid)) {
		$sql .= " and proid = '".$proid."' ";
	}
	$query = mysqli_query($conn,$sql);
	$row = mysqli_fetch_array($query);
	if($row['total']) {
		$count_num = $row['total'];
	} else {
		$count_num = "0";
	}
	return $count_num;
}
function bankname($bankid,$uid,$defaultname="默认") {
	global $conn;
	$sql = "select bankname from ".TABLE."bank where userid = '$uid' and bankid='$bankid'";
	$query = mysqli_query($conn,$sql);
	$row = mysqli_fetch_array($query);
	if($row['bankname']) {
		$bankname = $row['bankname'];
	} else {
		$bankname = $defaultname;
	}
	return $bankname;
}
function programname($proid,$uid,$defaultname="默认项目") {
	global $conn;
	$sql = "select proname from ".TABLE."program where userid = '$uid' and proid='$proid'";
	$query = mysqli_query($conn,$sql);
	$row = mysqli_fetch_array($query);
	if($row['proname']) {
		$proname = $row['proname'];
	} else {
		$proname = $defaultname;
	}
	return $proname;
}
function recordname($uid,$defaultname="系统账户") {
	global $conn;
	$sql = "select username from ".TABLE."user where uid = '$uid'";
	$query = mysqli_query($conn,$sql);
	$row = mysqli_fetch_array($query);
	if($row['username']) {
		$username = $row['username'];
	} else {
		$username = $defaultname;
	}
	return $username;
}
function query_once($uid,$id) {
	global $conn;
	$sql = "SELECT a.*,b.classname FROM ".TABLE."account as a INNER JOIN ".TABLE."account_class as b ON b.classid=a.acclassid ";
	$sql .= "where a.userid = '$uid' and ";
	$sql .= "a.acid = '$id'";
	$query = mysqli_query($conn,$sql);
	$resArr = array();
	while($row = mysqli_fetch_array($query)) {
		$resArr[] = $row;
	}
	return $resArr;
}
function db_list($dbname,$where,$orderby) {
	global $conn;
	$sql = "SELECT * FROM ".TABLE.$dbname." ".$where." ".$orderby;
	$query = mysqli_query($conn,$sql);
	$resArr = array();
	while($row = mysqli_fetch_array($query)) {
		$resArr[] = $row;
	}
	return $resArr;
}
function money_int_out($bankid,$money,$zhifu) {
	global $conn;
	if($zhifu=="1") {
		$sql = "update ".TABLE."bank set balancemoney=balancemoney+".$money." where bankid=".$bankid;
	} elseif($zhifu=="2") {
		$sql = "update ".TABLE."bank set balancemoney=balancemoney-".$money." where bankid=".$bankid;
	}
	$res = mysqli_query($conn,$sql);
}
function count_bank_money($bankid,$start_time,$end_time) {
	global $conn;
	global $userid;
	$where = "where userid='$userid' and zhifu='2' and bankid='".$bankid."' and actime >= '".strtotime($start_time." 00:00:00")."' and actime <= '".strtotime($end_time." 23:59:59")."'";
	$sql = "SELECT sum(acmoney) AS total FROM ".TABLE."account ".$where;
	$query = mysqli_query($conn,$sql);
	$row = mysqli_fetch_array($query);
	if($row['total']) {
		$count_num = $row['total'];
	} else {
		$count_num = "0.00";
	}
	return $count_num;
}
function month_type_count($typeid,$get_year,$userid) {
	$income_count_data = "";
	$income_count_list = total_count($typeid,$get_year,$userid,0);
	for ($b=1;$b<=12;$b++) {
		$month_income_num = "0";
		foreach($income_count_list as $countrow) {
			if($b == $countrow['month']) {
				$month_income_num = $countrow['total'];
				continue;
			}
		}
		$income_count_data .= $month_income_num.",";
	}
	$income_count_data = substr($income_count_data,0,-1);
	return $income_count_data;
}
?>

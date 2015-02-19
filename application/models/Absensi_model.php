<?php
class Absensi_model extends CI_Model {
		
	/**
	 * margin batas bawah dan atas absensi dalam array 
	 * waktu = array($urutan_hari => jam)
	 * 1 = senin
	 * 2 = selasa
	 * dst
	 */
	private $below_time = array(1=>"08:30:00",  //senin
								2=>"08:30:00",  //selasa
								3=>"08:30:00",  //rabu
								4=>"08:30:00",  //kamis
								5=>"08:30:00",  //jum'at
								6=>"00:00:00",  //sabtu
								7=>"00:00:00"); //minggu
	private $upper_time = array(1=>"17:00:00",  
								2=>"17:00:00",
								3=>"17:00:00",
								4=>"17:00:00",
								5=>"17:30:00",
								6=>"00:00:00",
								7=>"00:00:00");

	public function __construct()
	{
		$this->load->database(); 
	}
        
    public function get_day_of_month($year=FALSE, $month=FALSE){
		/**
		 * Gets the number of days from a given year and month.
		 * @return array
		 */
		$imonth = (empty($month)==TRUE)?(int)date('n'):$month;
		$iyear = (empty($year)===TRUE)?(int)date('Y'):$year;
        if(($imonth == date('n'))&&($iyear == date('Y'))){
			$the_days = date('j');
		}else{
			$the_days = cal_days_in_month(CAL_GREGORIAN,$imonth,$iyear);
		}
		return $the_days;
    }
    
    public function get_daily_record($nip = false, $year = false, $month = false, $day = false){
		/**
		 * Gets the daily record of arrival time, departure time, work time, and the second count in the office
		 * base on the fingerprint record.
		 * @return array
		 */
		$the_day = date('N', mktime(0,0,0,$month,$day, $year));
		$the_imargin = $year.'-'.$month.'-'.$day.' '.$this->below_time[$the_day];
		$the_jmargin = $year.'-'.$month.'-'.$day.' '.$this->upper_time[$the_day];
		$this->db->select("nip");
		$this->db->where(array("nip" => $nip,
						"YEAR(masuk)" => $year,
						"MONTH(masuk)" => $month,
						"DAY(masuk)" => $day));
		$query = $this->db->get("absensi");
		
		if($this->check_on_site($nip, $year, $month, $day)){
			return $this->check_on_site($nip, $year, $month, $day);
		}else if($query->num_rows()> 0 ){
			$this->db->select("DATE_FORMAT(MIN(absensi.masuk), '%T') AS entry_time");
			$this->db->select("DATE_FORMAT(MAX(absensi.pulang), '%T') AS exit_time");
			$this->db->select("SEC_TO_TIME(ABS(TIMESTAMPDIFF(SECOND,min(masuk),max(pulang)))) AS daily_time");
			$this->db->select("ABS(TIMESTAMPDIFF(SECOND,MIN(masuk),MAX(pulang))) AS daily_second");
			$this->db->select("ABS(TIMESTAMPDIFF(SECOND,'$the_imargin','$the_jmargin')) AS daily_max");
			$this->db->select("ABS(TIMESTAMPDIFF(SECOND,MIN(masuk),MAX(pulang)))/ABS(TIMESTAMPDIFF(SECOND,'$the_imargin','$the_jmargin'))*100 AS daily_pct");
			$this->db->where(array("nip" => $nip,
							"YEAR(masuk)" => $year,
							"MONTH(masuk)" => $month,
							"DAY(masuk)" => $day));
			$query = $this->db->get("absensi");
			return $query->row_array();
		}else if($this->check_holiday($year, $month, $day)){
			return $this->check_holiday($year, $month, $day);
		}else{
			return $this->get_daily_max($year, $month, $day);
		}
	}
	 
	public function get_daily_max($year = false, $month = false, $day = false){
		/**
		 * Gets the daily record of arrival time, departure time, work time, and the second count in the office
		 * base on the fingerprint record.
		 * @return array
		 */
		$the_day = date('N', mktime(0,0,0,$month,$day, $year));
		$the_imargin = $year.'-'.$month.'-'.$day.' '.$this->below_time[$the_day];
		$the_jmargin = $year.'-'.$month.'-'.$day.' '.$this->upper_time[$the_day];
		$query = $this->db->query("SELECT ABS(TIMESTAMPDIFF(SECOND,'$the_imargin','$the_jmargin')) AS daily_max FROM dual");
		$result =$query->row_array();
		$result['daily_second'] = 0;
		return($result);
	}
	
	public function seconds_to_hours($second){
		/**
		 * Just change from second to H:i:s format by the help of MySQL
		 * @return array
		 * 
		 */
		$query = $this->db->query("SELECT SEC_TO_TIME($second)FROM dual");
		return $query->row_array();
	}
	
	public function get_the_earlier($nip = false, $year = false, $month = false, $day = false){
		/**
		 * mengambil waktu pulang/datang yang tidak sesuai ketentuan
		 * @return array
		 * 
		 */
			$the_day = date('N', mktime(0,0,0,$month,$day, $year));
			$the_imargin = $year.'-'.$month.'-'.$day.' '.$this->below_time[$the_day];
			$the_jmargin = $year.'-'.$month.'-'.$day.' '.$this->upper_time[$the_day];
			$this->db->select("(TIMESTAMPDIFF(SECOND,MIN(masuk),'$the_imargin')) AS comer");
			$this->db->select("(TIMESTAMPDIFF(SECOND,'$the_jmargin',MAX(pulang))) AS goner");
			$this->db->where(array("nip"=>$nip, 
								"YEAR(masuk)" => $year,
								"MONTH(masuk)" => $month,
								"DAY(masuk)" => $day));
			$query = $this->db->get('absensi');
			$result =$query->row_array();
			if($result['comer'] > 0){
				$result['comer'] = NULL;
			}
			if($result['goner'] > 0){
				$result['goner'] = NULL;
			}
			return $result;
	}
	
	public function check_come_gone($nip = false, $year = false, $month = false, $day = false){
		$this->db->select("DATE_FORMAT(masuk,'%T')");
		$this->db->select("DATE_FORMAT(pulang,'%T')");
		$this->db->where(array("nip"=>$nip, 
					"YEAR(masuk)" => $year,
					"MONTH(masuk)" => $month,
					"DAY(masuk)" => $day));
		$query = $this->db->get('absensi');
		$result = $query->row_array();
		if($result['masuk'] == $result['pulang']){
			if($result['masuk'] == "00:00:00"){
				return "TA";
			}else if($result['masuk'] < "12:00:00"){
				return "TM";
			}else{
				return "TP";
			}
		}else{
			return false;
		}
	}
	
	public function get_employee($status=FALSE){
		if($status == TRUE){
			$this->db->where(array('status' => 'Aktif'));
		}
		$this->db->order_by('nama');
		$query = $this->db->get('pegawai');
		return $query->result();
	}
	
	public function check_on_site($nip = false, $year = false, $month = false, $day = false){
		$this->db->select('id, DAY(tanggal_mulai) AS TM, DAY(tanggal_selesai) AS TS');
		$this->db->where('nip', $nip);
		$this->db->where("(YEAR(tanggal_mulai) = '$year' AND MONTH(tanggal_mulai) = '$month' 
					OR YEAR(tanggal_selesai) = '$year' AND MONTH(tanggal_selesai) = '$month')");
		$query = $this->db->get('surat_tugas');
		foreach($query->result_array() as $res ){
			if($day <= $res['TS'] && $day >= $res['TM']){
				return array('TL'=>$res['id'], 'daily_max'=>0,'daily_second'=>0);
			}
		}
	}
	
	public function check_holiday($year = NULL, $month=NULL, $day=NULL){
		$the_day = date('N', mktime(0,0,0,$month,$day, $year));
		$query = $this->db->get_where('libur', 
						array('YEAR(tanggal)' => $year,
							'MONTH(tanggal)' => $month,
							'DAY(tanggal)' => $day));
		$libur['libur'] = null;
		if($the_day == 6){
			$libur['libur'] = 'Sabtu';
		}else if( $the_day == 7 ){
			$libur['libur'] = 'Minggu';
		}else if( $query->num_rows() > 0){
			$libur['libur'] = 'Nasional';
		}	
		$libur['daily_max'] = 0;
		$libur['daily_second'] = 0;		
		
		if(!empty($libur['libur'])){
			return $libur;
		}else{
			return FALSE;
		}
	}
}

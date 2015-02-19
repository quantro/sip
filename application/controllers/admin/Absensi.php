<?php
class Absensi extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('absensi_model');
	}

	public function index()
	{
		$emps = $this->absensi_model->get_employee(1);
		foreach($emps as $empl){
			$data['nip'] = $empl->nip;
			$data['nama'] = $empl->nama;
		}
		var_dump($this->absensi_model->get_daily_record("07000016",2014,12,19));
		var_dump($this->absensi_model->get_late_comer("07000016",2014,12,19));
		var_dump($this->absensi_model->get_early_goner("","07000016",2014,12,19));
	}

	public function bulanan($iyear = NULL, $imonth=NULL, $idays=NULL)
	{
		$month = (empty($imonth)==TRUE)?(int)date('n'):$imonth;
		$year = (empty($iyear)===TRUE)?(int)date('Y'):$iyear;
		$emps = $this->absensi_model->get_employee(1);
		$the_limit = $this->absensi_model->get_day_of_month($year,$month);
		$mnth = date('F Y', mktime(0, 0, 0, $month, 1, $year));
		$index = 1;
		foreach($emps as $empl){
			$data['bln'] = $mnth;
			$data['jml_kol'] = $the_limit; 
			$data['abs'][$index]['nip'] = $empl->nip;
			$data['abs'][$index]['nama'] = $empl->nama;
			$in_loop = 1;
			$daily_sec = 0;
			$daily_max = 0;
			for($in_loop; $in_loop<=$the_limit; $in_loop++){
				$daily_rec = $this->absensi_model->get_daily_record($empl->nip,$year, $month, $in_loop);
				$data['abs'][$index]['day'][$in_loop] = $daily_rec;
				$daily_sec += $daily_rec['daily_second'];
				$daily_max += $daily_rec['daily_max'];
			}
			$data['abs'][$index]['monthly_sec'] = $this->absensi_model->seconds_to_hours($daily_sec);
			$data['abs'][$index]['monthly_max'] = $this->absensi_model->seconds_to_hours($daily_max);
			$data['abs'][$index]['monthly_pct'] = $daily_sec/$daily_max*100;
			$index++;
		}
		//echo $this->table->generate();
		
		$this->load->view('absensi/bulanan',$data);
		echo '<pre>';
		//var_dump($data);
		echo '</pre>';
	}
      
    public function create()
	{
		$this->load->helper('form');
		$this->load->library('form_validation');

		$data['title'] = 'Create a news item';

		$this->form_validation->set_rules('title', 'Title', 'required');
		$this->form_validation->set_rules('text', 'text', 'required');

		if ($this->form_validation->run() === FALSE)
		{
			$this->load->view('templates/header', $data);
			$this->load->view('news/create');
			$this->load->view('templates/footer');

		}
		else
		{
			$this->news_model->set_news();
			$this->load->view('news/success');
		}
	}  
	
}

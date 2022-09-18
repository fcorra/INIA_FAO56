<?php

class METEO_DATA
{
	public float $Latitud;
	public float $Altitud;
	public $data = array(
			"Fecha" => array (),
			"Radiaci�n solar" => array (),
			"Temperatura m�nima" => array (),
			"Temperatura m�xima" => array (),
			"Humedad relativa media" => array (),
			"Velocidad del viento" => array (),
			"Radiaci�n extraterrestre" => array (),
			"Radiaci�n solar d�a despejado" => array (),
			"Radiaci�n neta solar" => array (),
			"Presi�n de vapor a saturaci�n" => array (),
			"Presi�n real de vapor" => array (),
			"Radiaci�n neta de onda larga" => array (),
			"Radiaci�n neta" => array (),
			"D�ficit de presi�n de vapor" => array (),
			"Pendiente presi�n de vapor vs temperatura" => array (),
			"Flujo de calor del suelo" => array (),
			"Evapotranspiraci�n de referencia" => array (),
	);

	public function __construct (
		$latitud, 
		$altitud,
		$albedo,	
		$fecha, 
		$radiacion_solar, 
		$temp_min, 
		$temp_max, 
		$humedad_relativa, 
		$velocidad_del_viento) 
	{
		$this->Latitud = (float) $latitud;
		$this->Altitud = (float) $altitud;
		$this->Albedo = (float) $albedo;
		$this->Psicrometrica = $this->psicrometrica ($this->Altitud);

		$max_day = count ($fecha);

		for ($i = 0; $i < $max_day; $i++) {

			array_push ($this->data["Fecha"], $fecha[$i]);
			array_push ($this->data["Radiaci�n solar"], $radiacion_solar[$i]);
			array_push ($this->data["Temperatura m�nima"], $temp_min[$i]);
			array_push ($this->data["Temperatura m�xima"], $temp_max[$i]);
			array_push ($this->data["Humedad relativa media"], $humedad_relativa[$i]);
			array_push ($this->data["Velocidad del viento"], $velocidad_del_viento[$i]);
			array_push ($this->data["Radiaci�n extraterrestre"],
				$this->r_a ($this->data["Fecha"][$i], $this->Latitud));
			array_push ($this->data["Radiaci�n solar d�a despejado"],
				$this->r_so ($this->Altitud, $this->data["Radiaci�n extraterrestre"][$i]));
			array_push ($this->data["Radiaci�n neta solar"],
				$this->r_ns ($this->Albedo, $this->data["Radiaci�n solar"][$i]));
			array_push ($this->data["Presi�n de vapor a saturaci�n"],
				$this->e_sat_med (
					$this->data["Temperatura m�xima"][$i],
					$this->data["Temperatura m�nima"][$i]
				)
			);
			array_push ($this->data["Presi�n real de vapor"],
				$this->e_a (
					$this->data["Humedad relativa media"][$i],
					$this->data["Temperatura m�xima"][$i],
					$this->data["Temperatura m�nima"][$i]
				)
			);
			array_push ($this->data["Radiaci�n neta de onda larga"],
			       	$this->r_nl (
					$this->data["Temperatura m�xima"][$i],
					$this->data["Temperatura m�nima"][$i],
					$this->data["Presi�n real de vapor"][$i],
					$this->data["Radiaci�n solar"][$i],
					$this->data["Radiaci�n solar d�a despejado"][$i]
				)
			);
			array_push ($this->data["Radiaci�n neta"],
			       	$this->rn (
					$this->data["Radiaci�n neta solar"][$i],
					$this->data["Radiaci�n neta de onda larga"][$i]
				)
			);
			array_push ($this->data["D�ficit de presi�n de vapor"],
			       	$this->dpv (
					$this->data["Presi�n de vapor a saturaci�n"][$i],
					$this->data["Presi�n real de vapor"][$i]
				)
			);
			array_push ($this->data["Pendiente presi�n de vapor vs temperatura"],
			       	$this->delta (
					$this->data["Temperatura m�xima"][$i],
					$this->data["Temperatura m�nima"][$i]
				)
			);
			array_push ($this->data["Flujo de calor del suelo"],
			       	$this-> g());
			array_push ($this->data["Evapotranspiraci�n de referencia"],
			       	$this->et0 (
					$this->data["Pendiente presi�n de vapor vs temperatura"][$i],
					$this->data["Radiaci�n neta"][$i],
					$this->data["Flujo de calor del suelo"][$i],
					$this->Psicrometrica,
					$this->data["Temperatura m�xima"][$i],
					$this->data["Temperatura m�nima"][$i],
					$this->data["Velocidad del viento"][$i],
					$this->data["D�ficit de presi�n de vapor"][$i]
				)
			);
		}
	}

	public function print () : array
	{
		//var_dump($this->data);
    return $this->data["Evapotranspiraci�n de referencia"];
	}

	private function r_a(string $fecha, float $lat) : float
	{
		
		/* La funci�n fecha devuelve un timestamp en distintos formatos,
		 * la opci�n "z" es el d�a juliano [0-365]. */
		$dia_juliano = date ("z", strtotime($fecha));

		$distancia_tierra_sol = 1 + 0.033 * cos (((2 * pi()) / 365) * $dia_juliano);
		$declinacion_solar = 0.409 * sin (((2 * pi()) / 365) * $dia_juliano - 1.39);
		$latitud_radianes = ($lat * pi()) / 180;
		$angulo_puesta_de_sol = acos (-tan ($latitud_radianes) * tan ($declinacion_solar));

		$uno = ((24 * 60) / pi()) * 0.082 * $distancia_tierra_sol;
		$dos = $angulo_puesta_de_sol * sin($latitud_radianes) * sin($declinacion_solar);
		$tre = cos($latitud_radianes) * cos($declinacion_solar) * sin($angulo_puesta_de_sol);
		$r_a = $uno * ($dos + $tre);
		return $r_a;
	}

	private function r_so(float $z, float $r_a) : float
	{
		$r_so = (0.75 + 2 * pow(10,-5) * $z) * $r_a;
		return $r_so;
	}

	private function e_sat ($temp) : float 
	{
		$e_sat = 0.6108 * exp((17.27 * $temp) / ($temp + 237.3));
		return $e_sat;
	}

	private function e_a (float $hr_media, float $temp_max, float $temp_min) : float
	{
		$temp_media = $this->temp_med ($temp_max, $temp_min);
		$e_a = $hr_media / 100 * $this->e_sat ($temp_media);
		return $e_a;
	}

	private function r_nl(float $t_max, float $t_min, float $e_a, float $r_s, float $r_so) : float
	{
		$uno = 4.903 * pow(10,-9) * ((pow(($t_max+273.16),4) + pow(($t_min+273.16),4)) * 0.5);
		$dos = (0.34 - 0.14 * sqrt($e_a));
		$tre = (1.35 * $r_s / $r_so - 0.35);
		$r_nl = $uno * $dos * $tre;
		return $r_nl;
	}

	private function r_ns (float $alpha, float $r_s) : float
	{
		$r_ns = (1 - $alpha) * $r_s;
		return $r_ns;
	}

	private function rn(float $rns, float $rnl) : float
	{
		$rn = $rns - $rnl;
		return $rn;
	}

	private function e_sat_med ($temp_max, $temp_min) : float 
	{
		$e_sat_med = ($this->e_sat($temp_max) + $this->e_sat($temp_min)) * 0.5;
		return $e_sat_med;
	}

	private function dpv($e_sat, $e_a) : float {
		return $e_sat - $e_a;
	}

	private function g() : float {
		return 0.0;
	}

	private function temp_med(float $temp_max, float $temp_min) : float
	{
		return ($temp_max + $temp_min) * 0.5;
	}

	private function psicrometrica(float $z) : float
	{
		$presion_atm = 101.3 * pow(((293 - 0.0065 * $z) / 293),5.26);
		$psi = (1.013 * pow(10, -3) * $presion_atm * pow(0.622, -1) * pow(2.45, -1));
    return $psi;
    //return 0.665 * pow(10, -3);
	}

	private function delta(float $temp_max, float $temp_min) : float
	{
		$temp_media = $this->temp_med ($temp_max, $temp_min);
		return 4098 * $this->e_sat ($temp_media) * pow(($temp_media + 237.3), -2);
	}

	private function et0(
		float $delta,
		float $rn,
		float $g,
		float $psi,
		float $temp_max,
		float $temp_min,
		float $v_viento,
		float $dpv
	) : float 
	{
		$temp_media = $this->temp_med ($temp_max, $temp_min);
		$num = 0.408 * $delta * ($rn - $g) + $psi * (900 / ($temp_media + 273)) * $v_viento * $dpv;
		$den = $delta + $psi * (1 + 0.34 * $v_viento);
		return $num / $den;
 	}

}
?>
